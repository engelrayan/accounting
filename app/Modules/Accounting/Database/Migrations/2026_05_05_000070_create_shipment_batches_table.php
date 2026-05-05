<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('shipment_batches')) {
            return;
        }

        Schema::create('shipment_batches', function (Blueprint $table) {
            $table->id();
            // Keep company_id as plain key (project has no companies table FK).
            $table->unsignedBigInteger('company_id')->index();
            $table->date('shipment_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'shipment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_batches');
    }
};
