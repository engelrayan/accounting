<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journal_entry_id')
                  ->constrained('journal_entries')
                  ->restrictOnDelete();

            $table->foreignId('account_id')
                  ->constrained('accounts')
                  ->restrictOnDelete();

            $table->string('description', 255)->nullable();

            // A line carries either a debit OR a credit, never both.
            // Enforced at the application layer (service + form request).
            $table->decimal('debit', 15, 2)->default(0.00);
            $table->decimal('credit', 15, 2)->default(0.00);

            // Multi-currency support
            $table->char('currency', 3)->default('SAR');
            $table->decimal('exchange_rate', 10, 6)->default(1.000000);

            $table->index('account_id');
            $table->index('journal_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
