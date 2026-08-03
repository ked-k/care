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
        Schema::create('policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category'); // gdpr, safeguarding, health_safety, clinical
            $table->text('description')->nullable();
            $table->string('version')->default('1.0');
            $table->foreignUuid('document_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->date('effective_date');
            $table->date('review_date');
            $table->boolean('is_mandatory_reading')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
