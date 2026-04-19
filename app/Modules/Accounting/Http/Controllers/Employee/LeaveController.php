<?php

namespace App\Modules\Accounting\Http\Controllers\Employee;

use App\Modules\Accounting\Models\LeaveRequest;
use App\Modules\Accounting\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    // ─── Employee: own leaves ────────────────────────────────────────────────

    public function index()
    {
        $employee = Auth::guard('employee')->user();

        $leaves = $employee->leaveRequests()
            ->with('leaveType')
            ->latest()
            ->paginate(15);

        return view('employee.leaves.index', compact('leaves', 'employee'));
    }

    public function create()
    {
        $employee   = Auth::guard('employee')->user();
        $leaveTypes = LeaveType::forCompany($employee->company_id)->active()->get();

        return view('employee.leaves.create', compact('leaveTypes', 'employee'));
    }

    public function store(Request $request)
    {
        $employee = Auth::guard('employee')->user();

        $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date', 'after_or_equal:today'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ], [
            'leave_type_id.required'    => 'نوع الإجازة مطلوب',
            'start_date.required'       => 'تاريخ البداية مطلوب',
            'start_date.after_or_equal' => 'تاريخ البداية يجب أن يكون اليوم أو بعده',
            'end_date.required'         => 'تاريخ النهاية مطلوب',
            'end_date.after_or_equal'   => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية أو مساوياً له',
        ]);

        $leaveType = LeaveType::where('id', $request->leave_type_id)
            ->where('company_id', $employee->company_id)
            ->firstOrFail();

        $days = LeaveRequest::calcDays($request->start_date, $request->end_date);

        if (!$leaveType->isUnlimited()) {
            $usedDays  = $employee->usedLeaveDays($leaveType->id, now()->year);
            $remaining = $leaveType->days_per_year - $usedDays;

            if ($days > $remaining) {
                return back()->withErrors([
                    'end_date' => "رصيد الإجازة غير كافٍ. المتبقي: {$remaining} يوم، المطلوب: {$days} يوم"
                ])->withInput();
            }
        }

        $overlap = $employee->leaveRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_date', '<=', $request->start_date)
                         ->where('end_date', '>=', $request->end_date);
                  });
            })->exists();

        if ($overlap) {
            return back()->withErrors([
                'start_date' => 'يوجد طلب إجازة آخر يتداخل مع هذه الفترة'
            ])->withInput();
        }

        $status = $leaveType->requires_approval ? 'pending' : 'approved';

        LeaveRequest::create([
            'company_id'    => $employee->company_id,
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'days'          => $days,
            'reason'        => $request->reason,
            'status'        => $status,
        ]);

        $message = $status === 'approved'
            ? 'تم تسجيل الإجازة تلقائياً (لا تتطلب موافقة)'
            : 'تم إرسال طلب الإجازة وهو قيد المراجعة';

        return redirect()->route('employee.leaves.index')->with('success', $message);
    }

    public function show(LeaveRequest $leave)
    {
        $employee = Auth::guard('employee')->user();

        if ($leave->employee_id !== $employee->id) {
            abort(403);
        }

        $leave->load('leaveType', 'reviewer');

        return view('employee.leaves.show', compact('leave', 'employee'));
    }

    public function cancel(LeaveRequest $leave)
    {
        $employee = Auth::guard('employee')->user();

        if ($leave->employee_id !== $employee->id) {
            abort(403);
        }

        if (!$leave->isCancellable()) {
            return back()->withErrors(['error' => 'لا يمكن إلغاء هذا الطلب']);
        }

        $leave->update(['status' => 'cancelled']);

        return redirect()->route('employee.leaves.index')->with('success', 'تم إلغاء طلب الإجازة');
    }

    // ─── Manager: team leaves ────────────────────────────────────────────────

    public function team(Request $request)
    {
        $manager = Auth::guard('employee')->user();

        // Make sure this employee is actually a manager
        if (!$manager->isManager()) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $query = $manager->teamLeaveRequests()
            ->with(['leaveType', 'employee'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $leaves      = $query->paginate(20)->withQueryString();
        $pendingCount = $manager->teamLeaveRequests()->where('status', 'pending')->count();

        return view('employee.leaves.team', compact('leaves', 'manager', 'pendingCount'));
    }

    public function teamApprove(LeaveRequest $leave)
    {
        $manager = Auth::guard('employee')->user();

        // Verify this leave belongs to one of the manager's subordinates
        $isSubordinate = $manager->subordinates()->where('id', $leave->employee_id)->exists();
        if (!$isSubordinate) {
            abort(403);
        }

        if (!$leave->isPending()) {
            return back()->with('error', 'هذا الطلب لم يعد قيد المراجعة');
        }

        $leave->update([
            'status'       => 'approved',
            'reviewed_by'  => $manager->id,
            'reviewed_at'  => now(),
            'review_notes' => 'تمت الموافقة من المدير المباشر',
        ]);

        return back()->with('success', "تمت الموافقة على إجازة {$leave->employee->name}");
    }

    public function teamReject(Request $request, LeaveRequest $leave)
    {
        $manager = Auth::guard('employee')->user();

        $isSubordinate = $manager->subordinates()->where('id', $leave->employee_id)->exists();
        if (!$isSubordinate) {
            abort(403);
        }

        if (!$leave->isPending()) {
            return back()->with('error', 'هذا الطلب لم يعد قيد المراجعة');
        }

        $request->validate([
            'review_notes' => ['required', 'string', 'max:500'],
        ], [
            'review_notes.required' => 'سبب الرفض مطلوب',
        ]);

        $leave->update([
            'status'       => 'rejected',
            'reviewed_by'  => $manager->id,
            'reviewed_at'  => now(),
            'review_notes' => $request->review_notes,
        ]);

        return back()->with('success', "تم رفض إجازة {$leave->employee->name}");
    }
}
