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
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('care_plan_id')->constrained('care_plans')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->nullable(); // Medication, Hygiene, Meal, etc.
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->foreignUuid('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('priority')->default(1); // 1=low, 5=urgent
            $table->boolean('requires_photo')->default(false);
            $table->boolean('requires_signature')->default(false);
            $table->string('recurring_pattern')->nullable(); // daily, weekly, monthly
            $table->foreignUuid('parent_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
