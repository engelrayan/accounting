<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add created_by / updated_by tracking to assets, partners, transactions.
 * Also adds updated_by to journal_entries (created_by already exists there).
 */
return new class extends Migration
{
    public function up(): void
    {
        // journal_entries already has created_by — just add updated_by
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('company_id');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('company_id');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });
    }
};
