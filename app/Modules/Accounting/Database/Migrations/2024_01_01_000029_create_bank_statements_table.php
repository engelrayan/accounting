<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('account_id');   // FK → accounts (bank/cash account)

            $table->date('statement_date');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);

            $table->enum('status', ['open', 'reconciled'])->default('open');
            $table->timestamp('reconciled_at')->nullable();

            $table->timestamps();

            $table->foreign('account_id')
                  ->references('id')->on('accounts')
                  ->restrictOnDelete();

            $table->index(['company_id', 'status'],                    'bs_company_status_idx');
            $table->index(['company_id', 'account_id', 'statement_date'], 'bs_company_account_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};
