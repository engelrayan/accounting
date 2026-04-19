<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Employee;
use App\Modules\Accounting\Models\PayrollLine;
use App\Modules\Accounting\Models\PayrollRun;
use App\Modules\Accounting\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollService $service,
    ) {}

    // =========================================================================
    // index
    // =========================================================================

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $runs = PayrollRun::forCompany($companyId)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->paginate(20);

        $totalPaid = PayrollRun::forCompany($companyId)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('total_net');

        return view('accounting.payroll.index', compact('runs', 'totalPaid'));
    }

    // =========================================================================
    // create
    // =========================================================================

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        $companyId     = $request->user()->company_id;
        $activeCount   = Employee::forCompany($companyId)->active()->count();
        $currentYear   = now()->year;
        $currentMonth  = now()->month;

        return view('accounting.payroll.create', compact('activeCount', 'currentYear', 'currentMonth'));
    }

    // =========================================================================
    // store
    // =========================================================================

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $validated = $request->validate([
            'period_year'  => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        try {
            $run = $this->service->createRun(
                $request->user()->company_id,
                (int) $validated['period_year'],
                (int) $validated['period_month'],
            );
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['run' => $e->getMessage()]);
        }

        return redirect()
            ->route('accounting.payroll.show', $run)
            ->with('success', "تم إنشاء مسير رواتب {$run->periodLabel()} بنجاح.");
    }

    // =========================================================================
    // show
    // =========================================================================

    public function show(Request $request, PayrollRun $payrollRun): View
    {
        if ($payrollRun->company_id !== $request->user()->company_id) abort(403);

        $payrollRun->load(['lines.employee']);

        return view('accounting.payroll.show', compact('payrollRun'));
    }

    // =========================================================================
    // editLine — صفحة تعديل بند موظف واحد
    // =========================================================================

    public function editLine(Request $request, PayrollRun $payrollRun, PayrollLine $payrollLine): View
    {
        Gate::authorize('can-write');

        if ($payrollRun->company_id !== $request->user()->company_id) abort(403);
        if ($payrollLine->payroll_run_id !== $payrollRun->id) abort(404);
        if ($payrollRun->isApproved()) {
            return redirect()
                ->route('accounting.payroll.show', $payrollRun)
                ->with('error', 'المسير معتمد ولا يمكن تعديله.');
        }

        $payrollLine->load('employee');

        return view('accounting.payroll.edit-line', compact('payrollRun', 'payrollLine'));
    }

    // =========================================================================
    // updateLine
    // =========================================================================

    public function updateLine(Request $request, PayrollRun $payrollRun, PayrollLine $payrollLine): RedirectResponse
    {
        Gate::authorize('can-write');

        if ($payrollRun->company_id !== $request->user()->company_id) abort(403);
        if ($payrollLine->payroll_run_id !== $payrollRun->id) abort(404);

        $validated = $request->validate([
            'basic_salary'            => ['required', 'numeric', 'min:0'],
            'payment_method'          => ['required', 'in:cash,bank,other'],
            'notes'                   => ['nullable', 'string', 'max:500'],
            'allowances'              => ['nullable', 'array'],
            'allowances.*.name'       => ['nullable', 'string', 'max:100'],
            'allowances.*.amount'     => ['nullable', 'numeric', 'min:0'],
            'deductions'              => ['nullable', 'array'],
            'deductions.*.name'       => ['nullable', 'string', 'max:100'],
            'deductions.*.amount'     => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $this->service->updateLine($payrollLine, $validated);
        } catch (\DomainException $e) {
            return back()->withErrors(['line' => $e->getMessage()]);
        }

        return redirect()
            ->route('accounting.payroll.show', $payrollRun)
            ->with('success', 'تم حفظ بيانات الموظف.');
    }

    // =========================================================================
    // approve
    // =========================================================================

    public function approve(Request $request, PayrollRun $payrollRun): RedirectResponse
    {
        Gate::authorize('can-write');

        if ($payrollRun->company_id !== $request->user()->company_id) abort(403);

        try {
            $this->service->approve($payrollRun, $request->user()->id);
        } catch (\Exception $e) {
            return back()->withErrors(['approve' => $e->getMessage()]);
        }

        return redirect()
            ->route('accounting.payroll.show', $payrollRun)
            ->with('success', "تم اعتماد مسير رواتب {$payrollRun->periodLabel()} وتسجيل القيد المحاسبي.");
    }
}
