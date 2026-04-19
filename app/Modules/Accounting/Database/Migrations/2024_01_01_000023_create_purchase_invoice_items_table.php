<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_invoice_id');
            $table->string('description', 500);
            $table->decimal('quantity',   10, 3)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total',      15, 2)->default(0);
            // No timestamps — line items are immutable once created

            $table->index('purchase_invoice_id', 'pii_invoice_idx');

            $table->foreign('purchase_invoice_id')
                  ->references('id')->on('purchase_invoices')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
    }
};
