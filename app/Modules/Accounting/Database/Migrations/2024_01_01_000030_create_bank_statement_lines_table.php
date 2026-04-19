<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_statement_id');

            $table->date('transaction_date');
            $table->string('description', 500);
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);

            $table->boolean('is_matched')->default(false);
            $table->unsignedBigInteger('journal_line_id')->nullable();  // matched journal line

            $table->timestamps();

            $table->foreign('bank_statement_id')
                  ->references('id')->on('bank_statements')
                  ->cascadeOnDelete();

            $table->foreign('journal_line_id')
                  ->references('id')->on('journal_lines')
                  ->nullOnDelete();

            $table->index(['bank_statement_id', 'is_matched'], 'bsl_statement_matched_idx');
            $table->index('journal_line_id',                   'bsl_journal_line_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
