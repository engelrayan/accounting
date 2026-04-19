<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('invoice_id')->nullable();   // null = general payment
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index('company_id',  'cpay_company_idx');
            $table->index('customer_id', 'cpay_customer_idx');
            $table->index('invoice_id',  'cpay_invoice_idx');

            $table->foreign('customer_id')
                  ->references('id')->on('customers')
                  ->cascadeOnDelete();

            $table->foreign('invoice_id')
                  ->references('id')->on('customer_invoices')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};
