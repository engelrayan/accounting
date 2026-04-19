<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Http\Requests\StoreAssetRequest;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Asset;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\ActivityLogService;
use App\Modules\Accounting\Services\AssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function __construct(
        private readonly AssetService       $service,
        private readonly ActivityLogService $log,
    ) {}

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $assets = Asset::forCompany($companyId)
            ->with('account')
            ->orderBy('status')
            ->orderBy('name')
            ->get();

        return view('accounting.assets.index', compact('assets'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        // Only cash/bank accounts shown — user doesn't pick GL accounts anymore
        $paymentAccounts = Account::forTenant($request->user()->company_id)
            ->active()
            ->where('type', 'asset')
            ->whereIn('code', ['1110', '1120'])   // الخزنة والبنك
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        // Fallback: if no specific accounts found, show all asset accounts
        if ($paymentAccounts->isEmpty()) {
            $paymentAccounts = Account::forTenant($request->user()->company_id)
                ->active()
                ->where('type', 'asset')
                ->orderBy('code')
                ->get(['id', 'code', 'name']);
        }

        return view('accounting.assets.create', compact('paymentAccounts'));
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        Gate::authorize('can-write');

        try {
            $asset = $this->service->createAsset(
                $request->validated(),
                $request->user()->company_id
            );
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['asset' => $e->getMessage()]);
        }

        $this->log->log(
            $request->user()->company_id,
            'created', 'asset', $asset->id,
            $asset->name,
            "أضاف الأصل الثابت [{$asset->name}] بتكلفة {$asset->purchase_cost}."
        );

        return redirect()
            ->route('accounting.assets.show', $asset)
            ->with('success', "تم إضافة الأصل [{$asset->name}] وتسجيل قيد الشراء بنجاح.");
    }

    public function show(Request $request, Asset $asset): View
    {
        $this->authorizeCompany($request, $asset);

        $asset->load(
            'account',
            'accumulatedDepreciationAccount',
            'depreciationExpenseAccount',
            'paymentAccount',
        );

        // Depreciation journal entries for this asset
        $depreciationEntries = JournalEntry::where('reference_type', 'asset')
            ->where('reference_id', $asset->id)
            ->where('description', 'like', 'Depreciation%')
            ->latest('entry_date')
            ->get();

        return view('accounting.assets.show', compact('asset', 'depreciationEntries'));
    }

    public function depreciate(Request $request, Asset $asset): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $asset);

        try {
            $this->service->recordDepreciation($asset);
        } catch (\Exception $e) {
            return back()->withErrors(['depreciate' => $e->getMessage()]);
        }

        $this->log->log(
            $request->user()->company_id,
            'depreciated', 'asset', $asset->id,
            $asset->name,
            "سجَّل إهلاكاً شهرياً للأصل [{$asset->name}]."
        );

        return back()->with('success', 'تم تسجيل الإهلاك الشهري بنجاح.');
    }

    // -------------------------------------------------------------------------

    private function accountsForSelect(int $companyId): \Illuminate\Support\Collection
    {
        return Account::forTenant($companyId)
            ->active()
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);
    }

    private function authorizeCompany(Request $request, Asset $asset): void
    {
        abort_if($asset->company_id !== $request->user()->company_id, 403);
    }
}
