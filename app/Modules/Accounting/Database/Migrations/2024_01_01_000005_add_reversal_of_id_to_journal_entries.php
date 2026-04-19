<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            // Links a reversal entry back to the original it cancels.
            // Opposite direction to reversed_by (original → reversal).
            // reversal_of_id is set on the new reversal entry.
            // reversed_by    is set on the original entry.
            $table->foreignId('reversal_of_id')
                  ->nullable()
                  ->after('reversed_by')
                  ->constrained('journal_entries')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropForeign(['reversal_of_id']);
            $table->dropColumn('reversal_of_id');
        });
    }
};
