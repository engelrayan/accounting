<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->smallInteger('year')->unsigned();       // e.g. 2025
            $table->date('starts_at');                      // 2025-01-01
            $table->date('ends_at');                        // 2025-12-31
            $table->enum('status', ['open', 'closed'])->default('open');

            // Populated when closing
            $table->decimal('net_profit', 15, 2)->nullable(); // + profit / - loss
            $table->unsignedBigInteger('closing_entry_id')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();

            $table->timestamps();

            // Only one record per company per year
            $table->unique(['company_id', 'year'], 'fy_company_year_unique');

            $table->index(['company_id', 'status'], 'fy_company_status_idx');

            $table->foreign('closing_entry_id')
                  ->references('id')->on('journal_entries')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_years');
    }
};
