<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for common query patterns:
 *  - Listing with company + status filter
 *  - Date-range reports (issue_date / payment_date)
 *  - Per-entity aging (customer/vendor + status)
 *  - GL aggregations by account
 *  - Balance-sheet queries by account type
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── invoices ─────────────────────────────────────────────────────────
        Schema::table('invoices', function (Blueprint $table) {
            // Listing filtered by company + status
            $table->index(['company_id', 'status'], 'inv_company_status_idx');
            // Period reports (P&L, AR statements)
            $table->index(['company_id', 'issue_date'], 'inv_company_date_idx');
            // AR aging per customer
            $table->index(['customer_id', 'status'], 'inv_customer_status_idx');
        });

        // ── payments ─────────────────────────────────────────────────────────
        Schema::table('payments', function (Blueprint $table) {
            // Payment reports filtered by company + date range
            $table->index(['company_id', 'payment_date'], 'pay_company_date_idx');
        });

        // ── purchase_invoices ────────────────────────────────────────────────
        Schema::table('purchase_invoices', function (Blueprint $table) {
            // Listing filtered by company + status
            $table->index(['company_id', 'status'], 'pi_company_status_idx');
            // Period reports
            $table->index(['company_id', 'issue_date'], 'pi_company_date_idx');
            // AP aging per vendor
            $table->index(['vendor_id', 'status'], 'pi_vendor_status_idx');
        });

        // ── purchase_payments ────────────────────────────────────────────────
        Schema::table('purchase_payments', function (Blueprint $table) {
            // Payment reports filtered by company + date range
            $table->index(['company_id', 'payment_date'], 'pp_company_date_idx');
        });

        // ── journal_lines ────────────────────────────────────────────────────
        Schema::table('journal_lines', function (Blueprint $table) {
            // GL balance aggregations: SUM(debit/credit) WHERE account_id = ?
            $table->index(['account_id', 'journal_entry_id'], 'jl_account_entry_idx');
        });

        // ── accounts ─────────────────────────────────────────────────────────
        Schema::table('accounts', function (Blueprint $table) {
            // Balance sheet / P&L: WHERE tenant_id = ? AND type IN (...)
            $table->index(['tenant_id', 'type'], 'acc_tenant_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('inv_company_status_idx');
            $table->dropIndex('inv_company_date_idx');
            $table->dropIndex('inv_customer_status_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('pay_company_date_idx');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropIndex('pi_company_status_idx');
            $table->dropIndex('pi_company_date_idx');
            $table->dropIndex('pi_vendor_status_idx');
        });

        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropIndex('pp_company_date_idx');
        });

        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropIndex('jl_account_entry_idx');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex('acc_tenant_type_idx');
        });
    }
};
