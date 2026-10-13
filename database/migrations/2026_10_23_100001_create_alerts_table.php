<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 16: a real alerting workflow — a late call, missed call,
     * missing record/evidence, or medication concern creates a row here
     * assigned to a named person, instead of only being a number on a
     * dashboard someone has to notice. See App\Support\AlertGenerator
     * (detection + creation) and App\Models\Alert.
     *
     * `alertable_type`/`alertable_id` point at whatever triggered the
     * alert (a Shift, CareCall, TaskLog, or Medication) — used only to
     * de-duplicate (never alert the same occurrence twice) and to link
     * back to it from the Alerts page; the alert survives even if that
     * record is later changed, since `summary` is a snapshot taken at
     * creation time, not a live lookup.
     */
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_user_id')->constrained('service_users')->cascadeOnDelete();
            $table->string('type'); // late_call, missed_call, missing_record, missing_evidence, medication_concern
            $table->nullableUuidMorphs('alertable');
            $table->text('summary');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open'); // open, acknowledged, resolved
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('acknowledged_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['service_user_id']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
