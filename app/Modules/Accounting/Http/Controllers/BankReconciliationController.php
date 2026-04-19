<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\BankStatement;
use App\Modules\Accounting\Services\ActivityLogService;
use App\Modules\Accounting\Services\BankReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BankReconciliationController extends Controller
{
    public function __construct(
        private readonly BankReconciliationService $service,
        private readonly ActivityLogService        $log,
    ) {}

    // -------------------------------------------------------------------------
    // Index — list all bank statements for this company
    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $statements = BankStatement::forCompany($companyId)
            ->with('account:id,code,name')
            ->orderByDesc('statement_date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('accounting.bank-reconciliation.index', compact('statements'));
    }

    // -------------------------------------------------------------------------
    // Create — form to start a new reconciliation
    // -------------------------------------------------------------------------

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        // Show asset accounts (bank / cash accounts live under type = asset)
        $accounts = Account::forTenant($companyId)
            ->active()
            ->where('type', 'asset')
            ->whereNull('parent_id')   // top-level or remove this if detailed accounts needed
            ->orWhere(function ($q) use ($companyId) {
                $q->forTenant($companyId)->active()->where('type', 'asset')->whereNotNull('parent_id');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        // De-duplicate the OR query above by just doing:
        $accounts = Account::forTenant($companyId)
            ->active()
            ->where('type', 'asset')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('accounting.bank-reconciliation.create', compact('accounts'));
    }

    // -------------------------------------------------------------------------
    // Store — persist new bank statement + lines
    // -------------------------------------------------------------------------

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'account_id'       => ['required', 'integer', 'exists:accounts,id'],
            'statement_date'   => ['required', 'date'],
            'opening_balance'  => ['required', 'numeric'],
            'closing_balance'  => ['required', 'numeric'],
            'lines'            => ['required', 'array', 'min:1'],
            'lines.*.transaction_date' => ['required', 'date'],
            'lines.*.description'      => ['required', 'string', 'max:500'],
            'lines.*.debit'            => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit'           => ['nullable', 'numeric', 'min:0'],
        ], [
            'account_id.required'          => 'يرجى اختيار الحساب البنكي.',
            'account_id.exists'            => 'الحساب المحدد غير موجود.',
            'statement_date.required'      => 'تاريخ الكشف مطلوب.',
            'opening_balance.required'     => 'الرصيد الافتتاحي مطلوب.',
            'closing_balance.required'     => 'الرصيد الختامي مطلوب.',
            'lines.required'               => 'يجب إضافة سطر واحد على الأقل.',
            'lines.min'                    => 'يجب إضافة سطر واحد على الأقل.',
            'lines.*.transaction_date.required' => 'تاريخ المعاملة مطلوب.',
            'lines.*.description.required'      => 'الوصف مطلوب.',
        ]);

        // Verify the account belongs to this company
        $account = Account::forTenant($companyId)
            ->where('id', $validated['account_id'])
            ->firstOrFail();

        try {
            $statement = $this->service->startReconciliation(
                $companyId,
                $account->id,
                $validated['statement_date'],
                (float) $validated['opening_balance'],
                (float) $validated['closing_balance'],
                $validated['lines'],
            );
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['bank_statement' => $e->getMessage()]);
        }

        $this->log->log(
            $companyId,
            'created', 'bank_statement', $statement->id,
            "كشف بنكي #{$statement->id}",
            "أنشأ كشف بنكي لحساب [{$account->name}] بتاريخ {$statement->statement_date->format('Y-m-d')}."
        );

        return redirect()
            ->route('accounting.bank-reconciliation.show', $statement)
            ->with('success', 'تم إنشاء كشف البنك. يمكنك الآن بدء عملية المطابقة.');
    }

    // -------------------------------------------------------------------------
    // Show — interactive reconciliation page
    // -------------------------------------------------------------------------

    public function show(Request $request, BankStatement $bankStatement): View
    {
        abort_if($bankStatement->company_id !== $request->user()->company_id, 403);

        $bankStatement->load([
            'lines.journalLine.entry',
            'account:id,code,name',
        ]);

        $unmatchedJournalLines = $this->service->getUnmatchedJournalLines($bankStatement);
        $summary               = $this->service->getSummary($bankStatement);

        return view('accounting.bank-reconciliation.show', compact(
            'bankStatement',
            'unmatchedJournalLines',
            'summary',
        ));
    }

    // -------------------------------------------------------------------------
    // Match — AJAX: link a bank line ↔ journal line
    // -------------------------------------------------------------------------

    public function match(Request $request, BankStatement $bankStatement): JsonResponse
    {
        abort_if($bankStatement->company_id !== $request->user()->company_id, 403);
        Gate::authorize('can-write');

        $validated = $request->validate([
            'statement_line_id' => ['required', 'integer'],
            'journal_line_id'   => ['required', 'integer'],
        ]);

        try {
            $this->service->matchLine(
                $bankStatement,
                (int) $validated['statement_line_id'],
                (int) $validated['journal_line_id'],
            );
        } catch (\DomainException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $summary = $this->service->getSummary($bankStatement->fresh()->load('lines'));

        return response()->json(['ok' => true, 'summary' => $summary]);
    }

    // -------------------------------------------------------------------------
    // Unmatch — AJAX: remove a match from a bank line
    // -------------------------------------------------------------------------

    public function unmatch(Request $request, BankStatement $bankStatement): JsonResponse
    {
        abort_if($bankStatement->company_id !== $request->user()->company_id, 403);
        Gate::authorize('can-write');

        $validated = $request->validate([
            'statement_line_id' => ['required', 'integer'],
        ]);

        try {
            $this->service->unmatchLine(
                $bankStatement,
                (int) $validated['statement_line_id'],
            );
        } catch (\DomainException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        $summary = $this->service->getSummary($bankStatement->fresh()->load('lines'));

        return response()->json(['ok' => true, 'summary' => $summary]);
    }

    // -------------------------------------------------------------------------
    // Complete — close the reconciliation
    // -------------------------------------------------------------------------

    public function complete(Request $request, BankStatement $bankStatement): RedirectResponse
    {
        abort_if($bankStatement->company_id !== $request->user()->company_id, 403);
        Gate::authorize('can-write');

        try {
            $this->service->complete($bankStatement);
        } catch (\DomainException $e) {
            return back()->withErrors(['reconciliation' => $e->getMessage()]);
        }

        $this->log->log(
            $request->user()->company_id,
            'updated', 'bank_statement', $bankStatement->id,
            "كشف بنكي #{$bankStatement->id}",
            "أغلق التسوية البنكية لحساب [{$bankStatement->account->name}]."
        );

        return redirect()
            ->route('accounting.bank-reconciliation.show', $bankStatement)
            ->with('success', 'تم إغلاق التسوية البنكية بنجاح.');
    }
}
