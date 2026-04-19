<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Partner;
use App\Modules\Accounting\Services\ActivityLogService;
use App\Modules\Accounting\Services\PartnerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PartnerController extends Controller
{
    public function __construct(
        private readonly PartnerService     $service,
        private readonly ActivityLogService $log,
    ) {}

    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $partners = Partner::forCompany($companyId)
            ->with('capitalAccount', 'drawingAccount')
            ->orderBy('name')
            ->get();

        $partnerData = $partners->map(fn ($partner) => [
            'partner'    => $partner,
            'capital'    => $this->service->getPartnerCapital($partner->id),
            'drawings'   => $this->service->getPartnerDrawings($partner->id),
            'balance'    => $this->service->getPartnerBalance($partner->id),
            'percentage' => $this->service->getPartnerPercentage($partner->id),
        ]);

        return view('accounting.partners.index', compact('partnerData'));
    }

    // -------------------------------------------------------------------------

    public function create(): View
    {
        Gate::authorize('can-write');

        return view('accounting.partners.create');
    }

    // -------------------------------------------------------------------------

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        Gate::authorize('can-write');

        try {
            $partner = $this->service->createPartner($validated, $request->user()->company_id);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['partner' => $e->getMessage()]);
        }

        $this->log->log(
            $request->user()->company_id,
            'created', 'partner', $partner->id,
            $partner->name,
            "أنشأ شريكاً جديداً [{$partner->name}]."
        );

        return redirect()
            ->route('accounting.partners.show', $partner)
            ->with('success', "تم إنشاء الشريك [{$partner->name}] بنجاح وتم إنشاء حساباته تلقائياً.");
    }

    // -------------------------------------------------------------------------

    public function show(Request $request, Partner $partner): View
    {
        $this->authorizeCompany($request, $partner);

        $partner->load('capitalAccount', 'drawingAccount');

        $capital    = $this->service->getPartnerCapital($partner->id);
        $drawings   = $this->service->getPartnerDrawings($partner->id);
        $balance    = $this->service->getPartnerBalance($partner->id);
        $percentage = $this->service->getPartnerPercentage($partner->id);
        $ledger     = $this->service->getPartnerLedger($partner->id);

        $cashAccounts = Account::forTenant($request->user()->company_id)
            ->active()
            ->where('type', 'asset')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('accounting.partners.show', compact(
            'partner',
            'capital',
            'drawings',
            'balance',
            'percentage',
            'ledger',
            'cashAccounts',
        ));
    }

    // -------------------------------------------------------------------------

    public function addCapital(Request $request, Partner $partner): RedirectResponse
    {
        $this->authorizeCompany($request, $partner);

        $validated = $request->validate([
            'amount'          => 'required|numeric|min:0.01',
            'cash_account_id' => 'required|integer|exists:accounts,id',
            'date'            => 'required|date',
            'description'     => 'nullable|string|max:500',
        ]);

        try {
            $this->service->addCapital(
                $partner,
                (float) $validated['amount'],
                (int)   $validated['cash_account_id'],
                $validated['date'],
                $validated['description'] ?? null,
            );
        } catch (\Exception $e) {
            return back()->withErrors(['add_capital' => $e->getMessage()]);
        }

        return back()->with('success', 'تم إضافة رأس المال بنجاح.');
    }

    // -------------------------------------------------------------------------

    public function withdraw(Request $request, Partner $partner): RedirectResponse
    {
        $this->authorizeCompany($request, $partner);

        $validated = $request->validate([
            'amount'          => 'required|numeric|min:0.01',
            'cash_account_id' => 'required|integer|exists:accounts,id',
            'date'            => 'required|date',
            'description'     => 'nullable|string|max:500',
        ]);

        try {
            $this->service->withdraw(
                $partner,
                (float) $validated['amount'],
                (int)   $validated['cash_account_id'],
                $validated['date'],
                $validated['description'] ?? null,
            );
        } catch (\Exception $e) {
            return back()->withErrors(['withdraw' => $e->getMessage()]);
        }

        return back()->with('success', 'تم تسجيل السحب بنجاح.');
    }

    // -------------------------------------------------------------------------

    private function authorizeCompany(Request $request, Partner $partner): void
    {
        abort_if($partner->company_id !== $request->user()->company_id, 403);
    }
}
