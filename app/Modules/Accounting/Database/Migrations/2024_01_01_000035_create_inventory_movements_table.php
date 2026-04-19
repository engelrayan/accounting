<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->enum('movement_type', ['purchase', 'sale', 'adjustment', 'transfer', 'return']);
            $table->decimal('quantity',   15, 3);   // موجب = وارد ، سالب = صادر
            $table->decimal('unit_cost',  15, 4)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->index('company_id',   'invmov_company_idx');
            $table->index('product_id',   'invmov_product_idx');
            $table->index('warehouse_id', 'invmov_warehouse_idx');
            $table->index(['reference_type', 'reference_id'], 'invmov_ref_idx');

            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->cascadeOnDelete();

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
