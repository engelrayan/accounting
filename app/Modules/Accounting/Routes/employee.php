<?php

use App\Modules\Accounting\Http\Controllers\Employee\AuthController as EmployeeAuthController;
use App\Modules\Accounting\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Modules\Accounting\Http\Controllers\Employee\LeaveController as EmployeeLeaveController;
use Illuminate\Support\Facades\Route;

// ── Employee Portal ── web only, NO admin auth ────────────────────────────────
Route::prefix('employee')->name('employee.')->group(function () {

    // Guest routes (no middleware needed)
    Route::get ('login',  [EmployeeAuthController::class, 'showLogin'])->name('login');
    Route::post('login',  [EmployeeAuthController::class, 'login']);
    Route::post('logout', [EmployeeAuthController::class, 'logout'])->name('logout');

    // Authenticated routes (employee guard only)
    Route::middleware('employee.auth')->group(function () {
        Route::get('dashboard', [EmployeeDashboardController::class, 'index'])  ->name('dashboard');
        Route::get('profile',   [EmployeeDashboardController::class, 'profile'])->name('profile');

        // Own leaves
        Route::get ('leaves',               [EmployeeLeaveController::class, 'index'])  ->name('leaves.index');
        Route::get ('leaves/create',        [EmployeeLeaveController::class, 'create']) ->name('leaves.create');
        Route::post('leaves',               [EmployeeLeaveController::class, 'store'])  ->name('leaves.store');
        Route::get ('leaves/{leave}',       [EmployeeLeaveController::class, 'show'])   ->name('leaves.show');
        Route::post('leaves/{leave}/cancel',[EmployeeLeaveController::class, 'cancel']) ->name('leaves.cancel');

        // Manager: team leave review
        Route::get ('team/leaves',                       [EmployeeLeaveController::class, 'team'])        ->name('leaves.team');
        Route::post('team/leaves/{leave}/approve',       [EmployeeLeaveController::class, 'teamApprove']) ->name('leaves.team.approve');
        Route::post('team/leaves/{leave}/reject',        [EmployeeLeaveController::class, 'teamReject'])  ->name('leaves.team.reject');
    });
});
