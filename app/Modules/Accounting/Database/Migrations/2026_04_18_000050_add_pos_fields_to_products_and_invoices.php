<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode', 120)->nullable()->after('code');
            $table->index(['company_id', 'barcode'], 'products_company_barcode_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('source', 30)->default('invoice')->after('payment_method');
            $table->unsignedBigInteger('created_by')->nullable()->after('company_id');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('subtotal');
            $table->index(['company_id', 'source'], 'invoices_company_source_idx');
            $table->index(['company_id', 'created_by'], 'invoices_company_cashier_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_company_barcode_idx');
            $table->dropColumn('barcode');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_company_source_idx');
            $table->dropIndex('invoices_company_cashier_idx');
            $table->dropColumn(['source', 'created_by', 'discount_amount']);
        });
    }
};
