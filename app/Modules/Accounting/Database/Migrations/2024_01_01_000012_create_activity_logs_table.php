<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();   // null = system action
            $table->string('action', 50);                        // created, updated, deleted, posted, reversed, depreciated, deactivated
            $table->string('entity_type', 50);                   // journal_entry, transaction, asset, partner, account, user
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_label', 255)->nullable();     // human-readable e.g. "JE-2024-00001"
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
