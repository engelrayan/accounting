<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            // Marks whether the entry was created automatically by the system
            // (vs. a legacy manual entry). All new entries will be true.
            $table->boolean('auto_generated')->default(true)->after('created_by');
        });

        // Back-fill: entries that have a reference_type are system-generated;
        // bare entries (no reference_type) may have been manual — mark them false.
        \DB::table('journal_entries')
            ->whereNull('reference_type')
            ->update(['auto_generated' => false]);
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn('auto_generated');
        });
    }
};
