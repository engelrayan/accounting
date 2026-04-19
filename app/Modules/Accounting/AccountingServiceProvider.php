<?php

namespace App\Modules\Accounting;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        // Views live in resources/views/accounting/ — standard Laravel location.
        // Reference them as: view('accounting.xyz')
        $this->loadViewsFrom(resource_path('views/accounting'), 'accounting');

        // Admin routes — require web session + admin auth
        Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/Routes/web.php');

        // Employee portal — web session only (uses employee guard internally)
        Route::middleware(['web'])
            ->group(__DIR__ . '/Routes/employee.php');
    }
}
