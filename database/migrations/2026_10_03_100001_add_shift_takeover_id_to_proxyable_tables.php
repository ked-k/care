<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stamps a nullable shift_takeover_id onto every record type a carer (or an
 * admin covering their shift) can create during a visit: a task completion,
 * a medication administration, a visit check-in, and a care-timeline note.
 * Left null for ordinary carer-entered records; set only when the record
 * was entered during an active App\Models\ShiftTakeover, so later views of
 * these records (the task list, the MAR chart, compliance review) can show
 * plainly that this one was entered by a manager/admin on the carer's
 * behalf, and why — without having to reconstruct that from timestamps.
 *
 * All four target tables, and shift_takeovers itself, use a UUID primary
 * key, so this is a brand-new nullable column on each (no existing column
 * is altered), which doesn't need doctrine/dbal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_logs', function (Blueprint $table) {
            $table->foreignUuid('shift_takeover_id')->nullable()->after('task_id')
                ->constrained('shift_takeovers')->nullOnDelete();
        });

        Schema::table('medication_administrations', function (Blueprint $table) {
            $table->foreignUuid('shift_takeover_id')->nullable()->after('shift_id')
                ->constrained('shift_takeovers')->nullOnDelete();
        });

        Schema::table('visit_checkins', function (Blueprint $table) {
            $table->foreignUuid('shift_takeover_id')->nullable()->after('shift_id')
                ->constrained('shift_takeovers')->nullOnDelete();
        });

        Schema::table('care_timeline_entries', function (Blueprint $table) {
            $table->foreignUuid('shift_takeover_id')->nullable()->after('service_user_id')
                ->constrained('shift_takeovers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('task_logs', function (Blueprint $table) {
            $table->dropForeign(['shift_takeover_id']);
            $table->dropColumn('shift_takeover_id');
        });

        Schema::table('medication_administrations', function (Blueprint $table) {
            $table->dropForeign(['shift_takeover_id']);
            $table->dropColumn('shift_takeover_id');
        });

        Schema::table('visit_checkins', function (Blueprint $table) {
            $table->dropForeign(['shift_takeover_id']);
            $table->dropColumn('shift_takeover_id');
        });

        Schema::table('care_timeline_entries', function (Blueprint $table) {
            $table->dropForeign(['shift_takeover_id']);
            $table->dropColumn('shift_takeover_id');
        });
    }
};
