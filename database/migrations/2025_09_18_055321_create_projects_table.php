<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('contract_id')->unique();
            $table->string('project_id')->unique(); // engineering identifier
            $table->string('project_name');
            $table->string('category')->nullable();
            $table->string('region')->nullable();
            $table->string('lgu')->nullable();
            $table->string('department')->nullable();
            $table->string('implementing_office')->nullable();
            $table->string('fund_source')->nullable();
            $table->string('implementation_type')->nullable(); // Contract or By Administration
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('revised_amount', 15, 2)->nullable();
            $table->string('location')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('project_engineer')->nullable();
            $table->string('contractor')->nullable();
            $table->integer('year_implemented')->nullable();
            $table->enum('status', ['ongoing','completed','terminated'])->default('ongoing');
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->string('image_path')->nullable();
            $table->string('document_path')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};