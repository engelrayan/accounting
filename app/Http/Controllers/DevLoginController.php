<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Quick login for local development only.
 * This controller must never be accessible in production.
 */
class DevLoginController extends Controller
{
    public function __construct()
    {
        abort_unless(app()->isLocal(), 404);
    }

    public function showForm(): View
    {
        return view('dev-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return back()->withErrors(['email' => 'Invalid credentials.']);
        }

        $request->session()->regenerate();

        return redirect('/admin/accounting/accounts');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();

        return redirect('/dev/login');
    }
}
