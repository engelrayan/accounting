<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('description', 191);
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly']);
            $table->date('start_date');
            $table->date('next_run_date');
            $table->date('last_run_date')->nullable();
            $table->date('end_date')->nullable();      // null = no end
            $table->boolean('is_active')->default(true);
            $table->json('lines');                     // [{account_id, type(debit/credit), amount, description}]
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['company_id', 'next_run_date', 'is_active'], 'rje_company_next_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_journal_entries');
    }
};
