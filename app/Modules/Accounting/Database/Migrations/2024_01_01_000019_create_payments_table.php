<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', [
                'cash', 'bank', 'wallet', 'instapay', 'cheque', 'card', 'other',
            ])->default('cash');
            $table->date('payment_date');
            $table->string('notes', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('company_id',  'pay_company_idx');
            $table->index('customer_id', 'pay_customer_idx');
            $table->index('invoice_id',  'pay_invoice_idx');
            $table->index('payment_date','pay_date_idx');

            $table->foreign('customer_id')
                  ->references('id')->on('customers')
                  ->cascadeOnDelete();

            $table->foreign('invoice_id')
                  ->references('id')->on('invoices')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
