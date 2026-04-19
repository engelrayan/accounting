<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Credit Notes ──────────────────────────────────────────────────────
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('credit_note_number', 30)->unique();
            $table->text('reason')->nullable();
            $table->decimal('amount', 15, 2)->default(0);      // subtotal before tax
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);       // amount + tax_amount
            $table->enum('status', ['draft', 'issued'])->default('draft');
            $table->date('issue_date');
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'invoice_id']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'issue_date']);
        });

        // ── Add credited_amount column to invoices ────────────────────────────
        // Tracks total credits applied via credit notes (separate from payments)
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('credited_amount', 15, 2)->default(0)->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('credited_amount');
        });
        Schema::dropIfExists('credit_notes');
    }
};
