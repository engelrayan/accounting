<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Modules\Accounting\Models\Employee;
use App\Modules\Accounting\Models\LeaveRequest;
use App\Modules\Accounting\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class LeaveManagementController extends Controller
{
    // -------------------------------------------------------------------------
    // Leave Types
    // -------------------------------------------------------------------------

    public function typesIndex(Request $request)
    {
        $companyId  = $request->user()->company_id;
        $leaveTypes = LeaveType::forCompany($companyId)->orderBy('name')->get();

        return view('accounting.leaves.types-index', compact('leaveTypes'));
    }

    public function typesStore(Request $request)
    {
        $companyId = $request->user()->company_id;

        $request->validate([
            'name'              => ['required', 'string', 'max:100'],
            'days_per_year'     => ['nullable', 'integer', 'min:1', 'max:365'],
            'requires_approval' => ['boolean'],
            'color'             => ['nullable', 'string', 'max:20'],
        ], [
            'name.required' => 'اسم نوع الإجازة مطلوب',
        ]);

        LeaveType::create([
            'company_id'        => $companyId,
            'name'              => $request->name,
            'days_per_year'     => $request->days_per_year,
            'requires_approval' => $request->boolean('requires_approval', true),
            'color'             => $request->color ?? '#6366f1',
            'is_active'         => true,
        ]);

        return redirect()->route('accounting.leaves.types')->with('success', 'تم إضافة نوع الإجازة');
    }

    public function typesToggle(LeaveType $leaveType)
    {
        $leaveType->update(['is_active' => !$leaveType->is_active]);

        return back()->with('success', 'تم تحديث حالة نوع الإجازة');
    }

    // -------------------------------------------------------------------------
    // Leave Requests (Admin)
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $query = LeaveRequest::forCompany($companyId)
            ->with(['employee', 'leaveType'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $leaves     = $query->paginate(20)->withQueryString();
        $employees  = Employee::forCompany($companyId)->active()->orderBy('name')->get();
        $leaveTypes = LeaveType::forCompany($companyId)->get();

        $stats = [
            'pending'  => LeaveRequest::forCompany($companyId)->where('status', 'pending')->count(),
            'approved' => LeaveRequest::forCompany($companyId)->where('status', 'approved')->count(),
            'rejected' => LeaveRequest::forCompany($companyId)->where('status', 'rejected')->count(),
        ];

        return view('accounting.leaves.index', compact('leaves', 'employees', 'leaveTypes', 'stats'));
    }

    public function show(Request $request, LeaveRequest $leave)
    {
        if ($leave->company_id !== $request->user()->company_id) abort(403);

        $leave->load(['employee.manager', 'leaveType', 'reviewer']);

        return view('accounting.leaves.show', compact('leave'));
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        if (!$leave->isPending()) {
            return back()->withErrors(['error' => 'الطلب ليس في حالة انتظار']);
        }

        $leave->update([
            'status'      => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'تمت الموافقة على طلب الإجازة');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $request->validate([
            'review_notes' => ['required', 'string', 'max:500'],
        ], [
            'review_notes.required' => 'سبب الرفض مطلوب',
        ]);

        if (!$leave->isPending()) {
            return back()->withErrors(['error' => 'الطلب ليس في حالة انتظار']);
        }

        $leave->update([
            'status'       => 'rejected',
            'reviewed_by'  => Auth::id(),
            'review_notes' => $request->review_notes,
            'reviewed_at'  => now(),
        ]);

        return back()->with('success', 'تم رفض طلب الإجازة');
    }
}
