<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy migration: skip if table already exists.
        // The newer structure is handled by 000052_create_price_list_items_table.
        if (Schema::hasTable('price_list_items')) {
            return;
        }

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_list_id');
            $table->string('governorate', 60);   // مفتاح المحافظة (e.g. 'cairo')
            $table->decimal('price', 15, 2)->default(0);
            $table->text('notes')->nullable();   // ملاحظة اختيارية لهذه المحافظة
            $table->timestamps();

            $table->foreign('price_list_id')
                  ->references('id')->on('price_lists')
                  ->cascadeOnDelete();

            // محافظة واحدة لكل قائمة أسعار
            $table->unique(['price_list_id', 'governorate'], 'pli_pl_gov_unique');
            $table->index('price_list_id', 'pli_pl_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
