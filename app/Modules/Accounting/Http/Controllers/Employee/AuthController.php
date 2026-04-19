<?php

namespace App\Modules\Accounting\Http\Controllers\Employee;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('employee')->check()) {
            return redirect()->route('employee.dashboard');
        }

        return view('employee.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'phone.required'    => 'رقم الجوال / رقم الموظف مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
        ]);

        $identifier = trim($request->phone);
        $remember   = $request->boolean('remember');

        // Try phone first, then employee_number fallback
        $loggedIn =
            Auth::guard('employee')->attempt(['phone'           => $identifier, 'password' => $request->password], $remember) ||
            Auth::guard('employee')->attempt(['employee_number' => $identifier, 'password' => $request->password], $remember);

        if (!$loggedIn) {
            return back()->withErrors(['phone' => 'رقم الجوال / رقم الموظف أو كلمة المرور غير صحيحة'])->withInput();
        }

        $employee = Auth::guard('employee')->user();

        if (!$employee->isActive()) {
            Auth::guard('employee')->logout();
            return back()->withErrors(['phone' => 'حسابك غير نشط، تواصل مع الإدارة'])->withInput();
        }

        $request->session()->regenerate();

        return redirect()->route('employee.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('employee')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('employee.login');
    }
}
