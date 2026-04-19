<?php

namespace App\Modules\Accounting\Http\Controllers\Employee;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $employee = Auth::guard('employee')->user();

        $pendingLeaves  = $employee->leaveRequests()->where('status', 'pending')->count();
        $approvedLeaves = $employee->leaveRequests()->where('status', 'approved')->count();
        $recentLeaves   = $employee->leaveRequests()
            ->with('leaveType')
            ->latest()
            ->limit(5)
            ->get();

        return view('employee.dashboard', compact('employee', 'pendingLeaves', 'approvedLeaves', 'recentLeaves'));
    }

    public function profile()
    {
        $employee = Auth::guard('employee')->user();

        return view('employee.profile', compact('employee'));
    }
}
