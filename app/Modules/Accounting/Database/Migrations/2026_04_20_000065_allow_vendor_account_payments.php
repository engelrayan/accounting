<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_payments') || ! Schema::hasColumn('purchase_payments', 'purchase_invoice_id')) {
            return;
        }

        $foreign = $this->foreignKeyName();

        if ($foreign) {
            DB::statement("ALTER TABLE `purchase_payments` DROP FOREIGN KEY `{$foreign}`");
        }

        DB::statement('ALTER TABLE `purchase_payments` MODIFY `purchase_invoice_id` BIGINT UNSIGNED NULL');

        if (! $this->foreignKeyName()) {
            DB::statement(
                'ALTER TABLE `purchase_payments` ADD CONSTRAINT `purchase_payments_purchase_invoice_id_foreign` ' .
                'FOREIGN KEY (`purchase_invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE'
            );
        }
    }

    public function down(): void
    {
        // Data safety: keep nullable because rows may represent payments against opening balances.
    }

    private function foreignKeyName(): ?string
    {
        $row = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'purchase_payments'
              AND COLUMN_NAME = 'purchase_invoice_id'
              AND REFERENCED_TABLE_NAME = 'purchase_invoices'
            LIMIT 1
        ");

        return $row?->CONSTRAINT_NAME;
    }
};
