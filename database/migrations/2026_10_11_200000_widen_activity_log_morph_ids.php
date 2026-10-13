<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 20 (audit depth, spatie/laravel-activitylog): the `activity_log`
 * table has existed since Batch 1's starter migrations, but it was never
 * actually used — the package itself was never installed, and the plain
 * `nullableMorphs('subject', 'subject')` / `nullableMorphs('causer',
 * 'causer')` calls in that original migration default the `{name}_id`
 * column to `unsignedBigInteger`. That's wrong for this app: almost every
 * model Batch 20 needs to log against (ServiceUser, CarePlan, Medication,
 * Consent, Policy, SafeguardingReport, BreachReport, SubjectAccessRequest,
 * ComplianceCheck, PolicyAcknowledgment, Invoice, InvoiceRun, Agency, ...)
 * uses HasUuids string primary keys, not auto-increment ints. Only
 * `causer_id` (always a User, which is auto-increment) was ever going to
 * fit — widened here too, for consistency and so a future causer type
 * isn't quietly blocked by the same bug.
 *
 * Table has never been populated (the package was never installed, so
 * nothing ever wrote to it) — this is a column-type fix, not a backfill,
 * so the drop-then-add below loses no data. Done as separate
 * Schema::table() calls (rather than ->change()) because ->change()
 * requires doctrine/dbal, which isn't installed in this app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('subject');
            $table->dropIndex('causer');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn(['subject_id', 'causer_id']);
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('subject_id', 36)->nullable()->after('subject_type');
            $table->string('causer_id', 36)->nullable()->after('causer_type');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id'], 'subject');
            $table->index(['causer_type', 'causer_id'], 'causer');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('subject');
            $table->dropIndex('causer');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn(['subject_id', 'causer_id']);
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->unsignedBigInteger('causer_id')->nullable()->after('causer_type');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id'], 'subject');
            $table->index(['causer_type', 'causer_id'], 'causer');
        });
    }
};
