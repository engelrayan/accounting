<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');                           // إجازة سنوية، مرضية…
            $table->unsignedSmallInteger('days_per_year')->nullable(); // null = غير محدود
            $table->boolean('requires_approval')->default(true);
            $table->string('color', 20)->default('#3b82f6'); // للـ UI
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id', 'lt_company_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
