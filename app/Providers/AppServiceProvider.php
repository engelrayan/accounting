<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // ── Authorization Gates ───────────────────────────────────────────────

        /**
         * admin-only: delete accounts, deactivate accounts, manage users.
         */
        Gate::define('admin-only', fn (User $user) => $user->isAdmin());

        /**
         * can-write: create / edit transactions, journal entries, assets, partners.
         * Both admin and accountant may write; viewer is read-only.
         */
        Gate::define('can-write', fn (User $user) => $user->canWrite());
    }
}
