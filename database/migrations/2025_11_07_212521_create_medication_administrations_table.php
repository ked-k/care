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
        Schema::create('medication_administrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('medication_id')->constrained()->cascadeOnDelete();
            $table->foreignId('administered_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('scheduled_time');
            $table->dateTime('actual_time')->nullable();
            $table->string('status'); // given, refused, missed, delayed
            $table->text('refusal_reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('witness_signature')->nullable();
            $table->foreignUuid('photo_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');

            $table->index(['medication_id', 'scheduled_time']);
            $table->index(['administered_by', 'actual_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_administrations');
    }
};
