<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('capital_account_id');
            $table->unsignedBigInteger('drawing_account_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index('company_id', 'partners_company_id_idx');
            $table->foreign('capital_account_id')->references('id')->on('accounts');
            $table->foreign('drawing_account_id')->references('id')->on('accounts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
