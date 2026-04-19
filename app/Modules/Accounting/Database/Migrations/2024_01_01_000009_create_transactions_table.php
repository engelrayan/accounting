<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('journal_entry_id');
            $table->enum('type', ['expense', 'income', 'transfer', 'capital_addition', 'withdrawal']);
            $table->unsignedBigInteger('from_account_id');   // CR side — where money comes from
            $table->unsignedBigInteger('to_account_id');     // DR side — where money goes to
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->date('transaction_date');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('journal_entry_id')->references('id')->on('journal_entries');
            $table->foreign('from_account_id')->references('id')->on('accounts');
            $table->foreign('to_account_id')->references('id')->on('accounts');
            $table->index('company_id');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
