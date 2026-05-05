<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_list_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_list_id');
            $table->unsignedBigInteger('customer_id');
            $table->timestamps();

            $table->foreign('price_list_id')
                  ->references('id')->on('price_lists')
                  ->cascadeOnDelete();

            $table->foreign('customer_id')
                  ->references('id')->on('customers')
                  ->cascadeOnDelete();

            $table->unique(['price_list_id', 'customer_id'], 'plc_pl_cust_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_customers');
    }
};
