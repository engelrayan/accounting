<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('entity_type', 50);          // e.g. 'transaction'
            $table->unsignedBigInteger('entity_id');
            $table->string('file_path');                 // relative path inside public disk
            $table->string('file_name');                 // original file name shown to user
            $table->string('file_type', 20);             // 'image' | 'excel' | 'pdf'
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id'], 'att_entity_idx');
            $table->index('company_id',                  'att_company_idx');
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
