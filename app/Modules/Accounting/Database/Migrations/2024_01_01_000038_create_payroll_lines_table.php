<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_run_id');
            $table->unsignedBigInteger('employee_id');
            $table->decimal('basic_salary',  15, 2)->default(0);
            $table->json('allowances')->nullable();   // [{name, amount}, …]
            $table->json('deductions')->nullable();   // [{name, amount}, …]
            $table->decimal('gross_salary',  15, 2)->default(0);
            $table->decimal('net_salary',    15, 2)->default(0);
            $table->enum('payment_method', ['cash', 'bank', 'other'])->default('bank');
            $table->text('notes')->nullable();

            $table->index('payroll_run_id', 'pl_run_idx');
            $table->index('employee_id',    'pl_emp_idx');

            $table->foreign('payroll_run_id')
                  ->references('id')->on('payroll_runs')
                  ->cascadeOnDelete();

            $table->foreign('employee_id')
                  ->references('id')->on('employees')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_lines');
    }
};
