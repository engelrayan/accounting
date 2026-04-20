<?php

use App\Http\Controllers\DevLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/admin');
    }

    return view('landing');
});

Route::get('/admin', fn() => redirect('/admin/accounting'))
    ->middleware('auth')
    ->name('admin.dashboard');

// Required by Laravel's auth middleware — redirects unauthenticated users here
Route::get('/login', fn() => redirect()->route('dev.login'))->name('login');

// ── Dev login (local environment only) ───────────────────────────────────────
// DevLoginController itself calls abort_unless(app()->isLocal(), 404)
// so these routes return 404 in production even if they're registered.
Route::prefix('dev')->name('dev.')->group(function () {
    Route::get('login',  [DevLoginController::class, 'showForm'])->name('login');
    Route::post('login', [DevLoginController::class, 'login']);
    Route::post('logout',[DevLoginController::class, 'logout'])->name('logout');
});
