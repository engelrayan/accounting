<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_modules')) {
            return;
        }

        DB::table('company_modules')
            ->where('module_key', 'customer_shipments')
            ->update([
                'is_enabled' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No rollback to avoid disabling a module unintentionally.
    }
};
