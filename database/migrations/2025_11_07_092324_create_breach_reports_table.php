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
        Schema::create('breach_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('agency_id')->nullable()->constrained('agencies')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('severity')->nullable(); // low, medium, high
            $table->text('action_taken')->nullable();
            $table->boolean('reported_to_ico')->default(false);
            $table->json('evidence')->nullable(); // list of media IDs, etc.
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
        Schema::dropIfExists('breach_reports');
    }
};
