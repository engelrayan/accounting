<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            // Human-readable sequential number per tenant per year: JE-2024-00001
            $table->string('entry_number', 20);

            // Polymorphic link to the source document (invoice, bill, payroll, etc.)
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->text('description');
            $table->date('entry_date');

            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');

            // Points to the correcting entry when this entry is reversed
            $table->foreignId('reversed_by')
                  ->nullable()
                  ->constrained('journal_entries')
                  ->nullOnDelete();

            $table->unsignedBigInteger('created_by');   // FK to users handled at app level
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'entry_number']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['tenant_id', 'entry_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
