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
        Schema::create('safeguarding_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_user_id')->nullable()->constrained('service_users')->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->foreignUuid('photo_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_long', 10, 7)->nullable();
            $table->foreignId('escalated_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open'); // open, under_review, closed
            $table->json('escalation_log')->nullable();
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
        Schema::dropIfExists('safeguarding_reports');
    }
};
