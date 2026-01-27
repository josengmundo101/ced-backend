<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();

            $table->string('original_name');     // original file name from user
            $table->string('stored_name');       // hashed file name in storage
            $table->string('file_type');         // pdf, docx, xlsx, jpg, etc.
            $table->integer('file_size');        // in bytes
            $table->string('file_path');         // storage path
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_latest')->default(true);

            $table->string('document_category')->nullable(); // Contracts, Reports, Photos
            $table->string('remarks')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_document');
    }
};