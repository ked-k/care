<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 17: a structured wellbeing-observation record — skin concerns,
     * mood/behaviour changes, nutrition, mobility, sleep, continence, etc —
     * distinct from both a free-text Timeline note (too unstructured to
     * review for patterns) and a full SafeguardingReport (reserved for
     * actual/suspected harm, not routine observations). See
     * App\Models\WellbeingObservation.
     *
     * Every observation also gets cross-posted to care_timeline_entries
     * (entry_type = 'wellbeing_observation') so it shows up in the existing
     * Timeline views without duplicating any UI, and — when severity is
     * "significant" — automatically raises an Alert via Alert::raiseFor(),
     * recorded here in alert_id so the observation and the alert it caused
     * stay linked.
     */
    public function up(): void
    {
        Schema::create('wellbeing_observations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_user_id')->constrained('service_users')->cascadeOnDelete();
            $table->foreignUuid('shift_takeover_id')->nullable()->constrained('shift_takeovers')->nullOnDelete();
            $table->string('category'); // skin_integrity, mood_behaviour, nutrition_hydration, mobility, sleep, continence, other
            $table->string('severity')->default('routine'); // routine, concern, significant
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->string('informed_person')->nullable(); // free text — e.g. "family", "GP", "manager on call"
            $table->boolean('follow_up_required')->default(false);
            $table->text('follow_up_notes')->nullable();
            $table->foreignUuid('media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->foreignUuid('alert_id')->nullable()->constrained('alerts')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamps();

            $table->index(['service_user_id', 'created_at']);
            $table->index(['agency_id', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wellbeing_observations');
    }
};
