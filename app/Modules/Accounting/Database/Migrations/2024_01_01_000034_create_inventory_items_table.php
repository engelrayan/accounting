<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->decimal('quantity_on_hand', 15, 3)->default(0);
            $table->decimal('average_cost',     15, 4)->default(0);
            $table->decimal('reorder_level',    15, 3)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id'], 'inv_product_warehouse_unique');
            $table->index('company_id',  'inv_company_idx');
            $table->index('product_id',  'inv_product_idx');
            $table->index('warehouse_id','inv_warehouse_idx');

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
        Schema::dropIfExists('inventory_items');
    }
};
