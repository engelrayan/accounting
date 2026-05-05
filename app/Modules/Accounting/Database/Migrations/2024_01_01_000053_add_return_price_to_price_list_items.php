<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('price_list_items') || Schema::hasColumn('price_list_items', 'return_price')) {
            return;
        }

        Schema::table('price_list_items', function (Blueprint $table) {
            $table->decimal('return_price', 15, 2)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('price_list_items') || !Schema::hasColumn('price_list_items', 'return_price')) {
            return;
        }

        Schema::table('price_list_items', function (Blueprint $table) {
            $table->dropColumn('return_price');
        });
    }
};
