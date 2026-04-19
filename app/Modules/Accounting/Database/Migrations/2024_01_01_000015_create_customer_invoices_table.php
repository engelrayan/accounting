<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('invoice_number', 50);
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->index('company_id',  'cinv_company_idx');
            $table->index('customer_id', 'cinv_customer_idx');
            $table->unique(['company_id', 'invoice_number'], 'cinv_number_unique');

            $table->foreign('customer_id')
                  ->references('id')->on('customers')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_invoices');
    }
};
