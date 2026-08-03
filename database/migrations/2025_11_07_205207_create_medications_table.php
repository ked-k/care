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
      Schema::create('medications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('service_user_id')->constrained()->cascadeOnDelete();
    $table->string('medication_name');
    $table->string('dosage');
    $table->string('frequency'); // e.g., "twice daily", "as needed"
    $table->string('administration_route'); // oral, topical, injection
    $table->time('scheduled_times')->nullable(); // can store JSON array
    $table->date('start_date');
    $table->date('end_date')->nullable();
    $table->boolean('is_prn')->default(false); // PRN = as needed
    $table->text('instructions')->nullable();
    $table->text('side_effects')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
