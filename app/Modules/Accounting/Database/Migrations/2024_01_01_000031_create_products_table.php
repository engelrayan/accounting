<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('code', 100)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['product', 'service'])->default('service');
            $table->string('unit', 50)->nullable();          // قطعة / ساعة / كجم / متر …
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('tax_rate', 10, 2)->default(0);
            $table->unsignedBigInteger('account_id')->nullable(); // حساب الإيراد الافتراضي
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id',         'prod_company_idx');
            $table->index(['company_id', 'type'],'prod_company_type_idx');
            $table->index('name',               'prod_name_idx');

            // رمز المنتج فريد داخل كل شركة (إذا أُدخل)
            $table->unique(['company_id', 'code'], 'prod_company_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
