<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->string('description');
            $table->decimal('quantity', 10, 3)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total', 15, 2);      // quantity × unit_price (stored for performance)

            $table->index('invoice_id', 'iitem_invoice_idx');
            $table->foreign('invoice_id')
                  ->references('id')->on('invoices')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
