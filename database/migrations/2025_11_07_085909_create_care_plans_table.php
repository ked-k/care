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
        Schema::create('care_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_user_id')->constrained('service_users')->cascadeOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->string('title')->nullable();
            $table->text('summary')->nullable();
            $table->date('review_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('plan_data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_plans');
    }
};
