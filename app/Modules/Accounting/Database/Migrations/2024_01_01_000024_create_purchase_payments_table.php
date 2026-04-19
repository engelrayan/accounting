<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', [
                'cash', 'bank', 'wallet', 'instapay', 'cheque', 'card', 'other',
            ])->default('cash');
            $table->date('payment_date');
            $table->string('notes', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — payments are append-only

            $table->index('company_id',           'pp_company_idx');
            $table->index('vendor_id',             'pp_vendor_idx');
            $table->index('purchase_invoice_id',   'pp_invoice_idx');
            $table->index('payment_date',          'pp_date_idx');

            $table->foreign('vendor_id')
                  ->references('id')->on('vendors')
                  ->cascadeOnDelete();

            $table->foreign('purchase_invoice_id')
                  ->references('id')->on('purchase_invoices')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
