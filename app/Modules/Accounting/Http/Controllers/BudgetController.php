<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Budget;
use App\Modules\Accounting\Models\BudgetLine;
use App\Modules\Accounting\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService,
    ) {}

    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $budgets = Budget::forCompany($companyId)
            ->orderByDesc('fiscal_year')
            ->orderBy('name')
            ->get();

        return view('accounting.budget.index', compact('budgets'));
    }

    // -------------------------------------------------------------------------

    public function create(): View
    {
        return view('accounting.budget.create', [
            'currentYear' => now()->year,
        ]);
    }

    // -------------------------------------------------------------------------

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ], [
            'name.required'        => 'اسم الميزانية مطلوب',
            'fiscal_year.required' => 'السنة المالية مطلوبة',
        ]);

        $budget = $this->budgetService->create(
            companyId: $companyId,
            name:      $request->name,
            year:      (int) $request->fiscal_year,
            notes:     $request->notes,
        );

        return redirect()
            ->route('accounting.budget.show', $budget)
            ->with('success', 'تم إنشاء الميزانية التقديرية');
    }

    // -------------------------------------------------------------------------

    public function show(Budget $budget, Request $request): View
    {
        $companyId = $request->user()->company_id;

        if ($budget->company_id !== $companyId) abort(403);

        $budget->load('lines.account');

        // For vs-actual tab
        $comparison = $this->budgetService->getBudgetVsActual($budget);

        // Accounts available for adding lines (income + expense leaf accounts)
        $accounts = Account::where('tenant_id', $companyId)
            ->whereIn('type', ['revenue', 'expense'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.budget.show', compact('budget', 'comparison', 'accounts'));
    }

    // -------------------------------------------------------------------------

    public function addLines(Budget $budget, Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        if ($budget->company_id !== $companyId) abort(403);

        if (!$budget->isDraft()) {
            return back()->withErrors(['error' => 'لا يمكن تعديل ميزانية غير مسودة']);
        }

        $request->validate([
            'lines'              => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer'],
            'lines.*.month'      => ['required', 'integer', 'min:1', 'max:12'],
            'lines.*.amount'     => ['required', 'numeric', 'min:0'],
        ]);

        $this->budgetService->saveLines($budget, $request->lines);

        return redirect()
            ->route('accounting.budget.show', $budget)
            ->with('success', 'تم حفظ بنود الميزانية');
    }

    // -------------------------------------------------------------------------

    public function activate(Budget $budget, Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        if ($budget->company_id !== $companyId) abort(403);

        if (!$budget->isDraft()) {
            return back()->withErrors(['error' => 'الميزانية ليست في حالة مسودة']);
        }

        $this->budgetService->activate($budget);

        return back()->with('success', 'تم تفعيل الميزانية التقديرية');
    }

    // -------------------------------------------------------------------------

    public function close(Budget $budget, Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        if ($budget->company_id !== $companyId) abort(403);

        $this->budgetService->close($budget);

        return back()->with('success', 'تم إغلاق الميزانية التقديرية');
    }
}
