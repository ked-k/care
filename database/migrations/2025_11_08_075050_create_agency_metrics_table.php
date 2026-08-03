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
       // 2025_11_05_000184_create_agency_metrics_table.php
Schema::create('agency_metrics', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
    $table->date('metric_date');
    $table->string('metric_type'); // visits_completed, medications_given, incidents_reported
    $table->decimal('value', 10, 2);
    $table->json('breakdown')->nullable(); // sub-metrics
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
    $table->timestamps();

    $table->unique(['agency_id', 'metric_date', 'metric_type']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_metrics');
    }
};
