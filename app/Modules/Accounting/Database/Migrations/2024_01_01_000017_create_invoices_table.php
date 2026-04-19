<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('invoice_number', 50);
            $table->text('notes')->nullable();
            $table->string('payment_method', 50)->nullable();  // cash, bank_transfer, cheque …
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'paid', 'cancelled'])->default('pending');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'invoice_number'], 'inv_number_unique');
            $table->index('company_id',  'inv_company_idx');
            $table->index('customer_id', 'inv_customer_idx');
            $table->index('status',      'inv_status_idx');

            $table->foreign('customer_id')
                  ->references('id')->on('customers')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
