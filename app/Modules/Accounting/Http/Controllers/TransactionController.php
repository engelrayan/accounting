<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Http\Requests\StoreTransactionRequest;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Partner;
use App\Modules\Accounting\Models\Transaction;
use App\Modules\Accounting\Services\ActivityLogService;
use App\Modules\Accounting\Services\AttachmentService;
use App\Modules\Accounting\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService  $service,
        private readonly ActivityLogService  $log,
        private readonly AttachmentService   $attachments,
    ) {}

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $transactions = Transaction::forCompany($companyId)
            ->with(['fromAccount:id,name,code', 'toAccount:id,name,code', 'creator:id,name,role', 'attachments'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(30);

        return view('accounting.transactions.index', compact('transactions'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        $cashAccounts = Account::forTenant($companyId)->active()
            ->where('type', 'asset')
            ->whereIn('code', ['1110', '1120'])
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        if ($cashAccounts->isEmpty()) {
            $cashAccounts = Account::forTenant($companyId)->active()
                ->where('type', 'asset')
                ->orderBy('code')
                ->get(['id', 'code', 'name']);
        }

        $expenseAccounts = Account::forTenant($companyId)->active()
            ->where('type', 'expense')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $revenueAccounts = Account::forTenant($companyId)->active()
            ->where('type', 'revenue')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $allAccounts = Account::forTenant($companyId)->active()
            ->orderBy('type')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        $partners = Partner::forCompany($companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('accounting.transactions.create', compact(
            'cashAccounts',
            'expenseAccounts',
            'revenueAccounts',
            'allAccounts',
            'partners',
        ));
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        Gate::authorize('can-write');

        try {
            $tx = $this->service->create(
                $request->validated(),
                $request->user()->company_id
            );
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['transaction' => $e->getMessage()]);
        }

        // ── Attachments (optional, multiple) ────────────────────────────────
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                try {
                    $this->attachments->store(
                        $file,
                        'transaction',
                        $tx->id,
                        $request->user()->company_id,
                    );
                } catch (\RuntimeException $e) {
                    // Non-fatal: continue with remaining files; log the failure.
                    report($e);
                }
            }
        }

        $this->log->log(
            $request->user()->company_id,
            'created', 'transaction', $tx->id,
            Transaction::typeLabel($tx->type),
            "سجَّل معاملة [{$tx->type}] بمبلغ {$tx->amount}."
        );

        return redirect()
            ->route('accounting.transactions.index')
            ->with('success', 'تم تسجيل المعاملة بنجاح.');
    }
}
