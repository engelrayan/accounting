<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name', 191);
            $table->unsignedSmallInteger('fiscal_year');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'fiscal_year']);
            $table->unique(['company_id', 'fiscal_year', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
