<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');   // 1–12
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->decimal('total_basic',      15, 2)->default(0);
            $table->decimal('total_allowances', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_gross',      15, 2)->default(0);
            $table->decimal('total_net',        15, 2)->default(0);
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'period_year', 'period_month'], 'pr_period_unique');
            $table->index('company_id', 'pr_company_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
