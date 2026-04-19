<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pre-aggregated monthly balances per account.
        // Updated whenever a journal entry is posted or reversed.
        // Avoids expensive SUM() scans on journal_lines for every report.
        Schema::create('account_balances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')
                  ->constrained('accounts')
                  ->cascadeOnDelete();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');   // 1–12

            $table->decimal('debit_total', 15, 2)->default(0.00);
            $table->decimal('credit_total', 15, 2)->default(0.00);

            // Snapshot: debit_total - credit_total
            // Positive = net debit, Negative = net credit
            $table->decimal('balance', 15, 2)->default(0.00);

            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // One row per account per period per tenant
            // Explicit short names to stay within MySQL's 64-char index name limit
            $table->unique(
                ['account_id', 'tenant_id', 'period_year', 'period_month'],
                'ab_account_tenant_period_unique'
            );
            $table->index(
                ['tenant_id', 'period_year', 'period_month'],
                'ab_tenant_period_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_balances');
    }
};
