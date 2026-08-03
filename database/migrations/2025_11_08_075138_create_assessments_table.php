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
      // 2025_11_05_000186_create_assessments_table.php
Schema::create('assessments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('service_user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('conducted_by')->constrained('users')->cascadeOnDelete();
    $table->string('assessment_type'); // falls_risk, nutrition, mental_capacity, etc.
    $table->json('questions_and_answers');
    $table->decimal('score', 5, 2)->nullable();
    $table->string('risk_level')->nullable(); // low, medium, high
    $table->text('recommendations')->nullable();
    $table->date('review_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
    $table->timestamps();

    $table->index(['service_user_id', 'assessment_type']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
