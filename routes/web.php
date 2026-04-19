<?php

use App\Http\Controllers\DevLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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
