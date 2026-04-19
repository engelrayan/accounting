<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('vendor_id');
            $table->string('invoice_number', 50);   // our internal ref: BILL-2026-0001
            $table->string('vendor_invoice_number', 100)->nullable(); // vendor's own number
            $table->text('notes')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->decimal('subtotal',         15, 2)->default(0);
            $table->decimal('tax_rate',         10, 2)->default(0);
            $table->decimal('tax_amount',       15, 2)->default(0);
            $table->decimal('amount',           15, 2)->default(0);
            $table->decimal('paid_amount',      15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'paid', 'cancelled'])->default('pending');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'invoice_number'], 'pi_number_unique');
            $table->index('company_id', 'pi_company_idx');
            $table->index('vendor_id',  'pi_vendor_idx');
            $table->index('status',     'pi_status_idx');

            $table->foreign('vendor_id')
                  ->references('id')->on('vendors')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
