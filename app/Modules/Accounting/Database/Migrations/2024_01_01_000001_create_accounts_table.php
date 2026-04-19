<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('accounts')
                  ->restrictOnDelete();

            $table->string('code', 20);
            $table->string('name', 100);

            $table->enum('type', [
                'asset',
                'liability',
                'equity',
                'revenue',
                'expense',
            ]);

            // Derived from type, stored explicitly to avoid joins in queries
            $table->enum('normal_balance', ['debit', 'credit']);

            $table->boolean('is_system')->default(false);   // protected accounts
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            // One code per tenant
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
