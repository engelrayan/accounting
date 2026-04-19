<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // subtotal = sum of line items before tax
            $table->decimal('subtotal', 15, 2)->default(0)->after('amount');
            // tax_rate stored per-invoice so old invoices stay correct if rate changes
            $table->decimal('tax_rate', 5, 2)->default(0)->after('subtotal');
            // tax_amount = subtotal × tax_rate / 100
            $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_rate');
            // amount = subtotal + tax_amount  (was previously = subtotal)
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'tax_rate', 'tax_amount']);
        });
    }
};
