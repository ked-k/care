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
       // 2025_11_05_000181_create_notifications_table.php
Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('type'); // shift_reminder, medication_due, policy_update, safeguarding_alert
    $table->string('title');
    $table->text('message');
    $table->string('priority')->default('normal'); // low, normal, high, urgent
    $table->json('data')->nullable(); // related entity IDs
    $table->dateTime('read_at')->nullable();
    $table->dateTime('action_taken_at')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'read_at']);
    $table->index(['type', 'created_at']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
