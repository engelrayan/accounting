<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('employee_number', 50);
            $table->string('name');
            $table->string('national_id', 50)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->date('hire_date');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->string('bank_account', 100)->nullable();
            $table->string('iban', 50)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'employee_number'], 'emp_company_number_unique');
            $table->index('company_id', 'emp_company_idx');
            $table->index(['company_id', 'status'], 'emp_company_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
