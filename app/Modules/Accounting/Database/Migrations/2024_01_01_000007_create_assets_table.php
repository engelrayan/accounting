<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name', 150);
            $table->string('category', 50);              // vehicle, equipment, furniture, etc.
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0.00);   // residual value
            $table->unsignedSmallInteger('useful_life');              // stored in months

            // GL accounts linked to this asset
            $table->foreignId('account_id')                            // DR on purchase (e.g. Equipment)
                  ->constrained('accounts')->restrictOnDelete();
            $table->foreignId('accumulated_depreciation_account_id')  // CR on depreciation
                  ->constrained('accounts')->restrictOnDelete();
            $table->foreignId('depreciation_expense_account_id')      // DR on depreciation
                  ->constrained('accounts')->restrictOnDelete();
            $table->foreignId('payment_account_id')                   // CR on purchase (Cash/Bank)
                  ->constrained('accounts')->restrictOnDelete();

            // Depreciation tracking
            $table->unsignedSmallInteger('depreciated_months')->default(0);

            $table->enum('status', ['active', 'fully_depreciated', 'disposed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
