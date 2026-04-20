<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'payment_method')) {
            return;
        }

        DB::statement(
            "ALTER TABLE `payments` MODIFY `payment_method` ENUM('cash','bank','wallet','instapay','cheque','card','other','settlement') NOT NULL DEFAULT 'cash'"
        );
    }

    public function down(): void
    {
        // Data safety: keep the wider enum because existing rows may use settlement.
    }
};
