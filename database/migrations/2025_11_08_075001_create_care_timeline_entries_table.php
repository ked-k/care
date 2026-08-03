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
        Schema::create('care_timeline_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_user_id')->constrained()->cascadeOnDelete();
            $table->string('entry_type'); // meal, activity, mood, medication, observation
            $table->text('content');
            $table->foreignUuid('media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->boolean('visible_to_family')->default(true);
            $table->json('metadata')->nullable(); // mood score, meal percentage, etc.
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamps();

            $table->index(['service_user_id', 'created_at']);
            $table->index('entry_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_timeline_entries');
    }
};
