<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('invoice_id');
            $table->index('product_id', 'iitem_product_idx');
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('purchase_invoice_id');
            $table->index('product_id', 'pii_product_idx');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex('iitem_product_idx');
            $table->dropColumn('product_id');
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropIndex('pii_product_idx');
            $table->dropColumn('product_id');
        });
    }
};
