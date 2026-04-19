<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days');             // محسوبة تلقائياً
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();  // user_id (admin)
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('company_id',   'lr_company_idx');
            $table->index('employee_id',  'lr_employee_idx');
            $table->index('status',       'lr_status_idx');
            $table->index('start_date',   'lr_start_idx');

            $table->foreign('employee_id')
                  ->references('id')->on('employees')
                  ->cascadeOnDelete();

            $table->foreign('leave_type_id')
                  ->references('id')->on('leave_types')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
