<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('shipment_entries')) {
            return;
        }

        Schema::create('shipment_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_batch_id')->constrained('shipment_batches')->cascadeOnDelete();
            // Keep company_id as plain key (project has no companies table FK).
            $table->unsignedBigInteger('company_id');
            $table->date('shipment_date');
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_list_id')->nullable()->constrained('price_lists')->nullOnDelete();
            $table->string('price_source', 30)->nullable();
            $table->enum('shipment_type', ['delivery', 'return'])->default('delivery');
            $table->decimal('quantity', 8, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->string('entry_code', 6);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'shipment_date', 'entry_code'], 'shipment_entries_company_date_code_unique');
            $table->index(['company_id', 'shipment_date']);
            $table->index(['customer_id', 'shipment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_entries');
    }
};
