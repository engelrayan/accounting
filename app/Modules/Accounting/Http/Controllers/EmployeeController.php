<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Employee;
use App\Modules\Accounting\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService,
    ) {}

    // =========================================================================

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $query = Employee::forCompany($companyId)->orderBy('name');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('employee_number', 'like', "%{$q}%")
                  ->orWhere('department', 'like', "%{$q}%")
                  ->orWhere('position', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        $employees = $query->paginate(25)->withQueryString();

        $totalCount     = Employee::forCompany($companyId)->count();
        $activeCount    = Employee::forCompany($companyId)->active()->count();
        $totalSalary    = Employee::forCompany($companyId)->active()->sum('basic_salary');
        $departments    = Employee::forCompany($companyId)->whereNotNull('department')
                            ->distinct()->pluck('department');

        return view('accounting.employees.index', compact(
            'employees', 'totalCount', 'activeCount', 'totalSalary', 'departments'
        ));
    }

    // =========================================================================

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        $companyId  = $request->user()->company_id;
        $nextNumber = $this->payrollService->nextEmployeeNumber($companyId);
        $managers   = Employee::forCompany($companyId)->active()->orderBy('name')->get();

        return view('accounting.employees.create', compact('nextNumber', 'managers'));
    }

    // =========================================================================

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'employee_number' => ['required', 'string', 'max:50'],
            'name'            => ['required', 'string', 'max:191'],
            'national_id'     => ['nullable', 'string', 'max:50'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'email'           => ['nullable', 'email', 'max:191'],
            'department'      => ['nullable', 'string', 'max:100'],
            'position'        => ['nullable', 'string', 'max:100'],
            'manager_id'      => ['nullable', 'exists:employees,id'],
            'hire_date'       => ['required', 'date'],
            'basic_salary'    => ['required', 'numeric', 'min:0'],
            'bank_account'    => ['nullable', 'string', 'max:100'],
            'iban'            => ['nullable', 'string', 'max:50'],
            'status'          => ['required', 'in:active,inactive'],
        ], [
            'employee_number.required' => 'رقم الموظف مطلوب.',
            'name.required'            => 'اسم الموظف مطلوب.',
            'hire_date.required'       => 'تاريخ التعيين مطلوب.',
            'basic_salary.required'    => 'الراتب الأساسي مطلوب.',
        ]);

        // Unique number per company
        $exists = Employee::where('company_id', $companyId)
            ->where('employee_number', $validated['employee_number'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['employee_number' => 'رقم الموظف مستخدم بالفعل.']);
        }

        // Default password = phone number (if provided) or employee_number
        $defaultPassword = $validated['phone'] ?? $validated['employee_number'];

        $employee = Employee::create(array_merge($validated, [
            'company_id' => $companyId,
            'password'   => Hash::make($defaultPassword),
        ]));

        return redirect()
            ->route('accounting.employees.index')
            ->with('success', "تم إضافة الموظف [{$employee->name}] بنجاح. كلمة المرور الافتراضية: {$defaultPassword}");
    }

    // =========================================================================

    public function edit(Request $request, Employee $employee): View
    {
        Gate::authorize('can-write');

        if ($employee->company_id !== $request->user()->company_id) abort(403);

        // Exclude the employee themselves from manager list (can't manage themselves)
        $managers = Employee::forCompany($request->user()->company_id)
            ->active()
            ->where('id', '!=', $employee->id)
            ->orderBy('name')
            ->get();

        return view('accounting.employees.edit', compact('employee', 'managers'));
    }

    // =========================================================================

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('can-write');

        if ($employee->company_id !== $request->user()->company_id) abort(403);

        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'employee_number' => ['required', 'string', 'max:50'],
            'name'            => ['required', 'string', 'max:191'],
            'national_id'     => ['nullable', 'string', 'max:50'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'email'           => ['nullable', 'email', 'max:191'],
            'department'      => ['nullable', 'string', 'max:100'],
            'position'        => ['nullable', 'string', 'max:100'],
            'manager_id'      => ['nullable', 'exists:employees,id'],
            'hire_date'       => ['required', 'date'],
            'basic_salary'    => ['required', 'numeric', 'min:0'],
            'bank_account'    => ['nullable', 'string', 'max:100'],
            'iban'            => ['nullable', 'string', 'max:50'],
            'status'          => ['required', 'in:active,inactive'],
        ]);

        $exists = Employee::where('company_id', $companyId)
            ->where('employee_number', $validated['employee_number'])
            ->where('id', '!=', $employee->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['employee_number' => 'رقم الموظف مستخدم بالفعل.']);
        }

        $employee->update($validated);

        return redirect()
            ->route('accounting.employees.index')
            ->with('success', "تم تحديث بيانات الموظف [{$employee->name}] بنجاح.");
    }

    // =========================================================================

    public function toggle(Request $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('can-write');

        if ($employee->company_id !== $request->user()->company_id) abort(403);

        $employee->update(['status' => $employee->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', 'تم تحديث حالة الموظف.');
    }

    // =========================================================================

    public function resetPassword(Request $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('can-write');

        if ($employee->company_id !== $request->user()->company_id) abort(403);

        // Reset to phone (if available) or employee_number
        $newPassword = $employee->phone ?? $employee->employee_number;

        $employee->update(['password' => Hash::make($newPassword)]);

        return back()->with('success',
            "تمت إعادة تعيين كلمة المرور للموظف [{$employee->name}]. كلمة المرور الجديدة: {$newPassword}"
        );
    }
}
