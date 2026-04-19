<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedTinyInteger('period_month');   // 1-12
            $table->decimal('amount', 18, 2)->default(0);
            $table->timestamps();

            $table->foreign('budget_id')->references('id')->on('budgets')->cascadeOnDelete();
            $table->unique(['budget_id', 'account_id', 'period_month']);
            $table->index(['budget_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
    }
};
