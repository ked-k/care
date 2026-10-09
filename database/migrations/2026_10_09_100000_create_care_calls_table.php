<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 14: a "call" is a welfare-check visit (morning/lunch/evening/
     * bedtime style) that is NOT a staffing Shift. Deliberately a separate
     * table rather than extra Shift rows — RotaPeriod::generateTimesheets()
     * picks firstWhere('shift_type', 'day'|'night') per carer/date and
     * timesheet_entries has exactly one day_shift_id/night_shift_id column,
     * so a service user with 3+ Shift rows in a day would silently corrupt
     * payroll (extra time just vanishes). Calls never touch payroll.
     *
     * No stored status for the day-to-day states (Upcoming/In Progress/
     * Completed/Late/Missed) — those are computed live from
     * scheduled_start/scheduled_end vs actual_start/actual_end by
     * App\Support\CareCallStatus, the same helper used for Shift-based
     * calls in the Care Call Timeline. `status` here only carries the one
     * state that can't be derived from time: whether a manager has
     * explicitly cancelled it.
     */
    public function up(): void
    {
        Schema::create('care_calls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('call_label')->nullable(); // e.g. "Lunch" — falls back to a time-of-day guess when blank
            $table->dateTime('scheduled_start');
            $table->dateTime('scheduled_end');
            $table->dateTime('actual_start')->nullable(); // check-in
            $table->dateTime('actual_end')->nullable();   // check-out
            $table->string('status')->default('scheduled'); // scheduled, cancelled
            $table->text('notes')->nullable(); // intended care for this call, set when scheduling
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_user_id', 'scheduled_start']);
            $table->index(['agency_id', 'scheduled_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_calls');
    }
};
