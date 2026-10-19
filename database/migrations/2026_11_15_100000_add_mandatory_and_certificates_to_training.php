<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 36: the "training matrix" feature the user asked for after seeing
 * it in a competitor's ad (123tenders) — a grid of staff x training module
 * showing certificate progress, with renewal reminders. Training today
 * (Batch 2) has no "mandatory" flag and no certificate/expiry concept at
 * all, so both halves of this are new columns, not a repurposing of
 * anything existing.
 *
 * is_mandatory / renewal_period_days live on training_modules (a property
 * of the module itself — e.g. "Safeguarding Awareness" is mandatory and
 * renews every 365 days for everyone who takes it).
 *
 * certificate_media_id follows this app's existing convention for an
 * optional file attached to a record (see task_logs.photo_id): a plain
 * uuid column, not a DB foreign key — the MediaFile row is created first,
 * then its own related_type/related_id columns are pointed back at this
 * row, the same two-step App\Livewire\Task\TaskListComponent::completeTask()
 * already uses for task photos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_modules', function (Blueprint $table) {
            $table->boolean('is_mandatory')->default(false)->after('category');
            $table->unsignedInteger('renewal_period_days')->nullable()->after('is_mandatory'); // e.g. 365 for an annual refresher; null = doesn't expire
        });

        Schema::table('training_progress', function (Blueprint $table) {
            $table->uuid('certificate_media_id')->nullable()->after('score');
            $table->date('expires_at')->nullable()->after('certificate_media_id');
            $table->timestamp('last_reminded_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('training_modules', function (Blueprint $table) {
            $table->dropColumn(['is_mandatory', 'renewal_period_days']);
        });

        Schema::table('training_progress', function (Blueprint $table) {
            $table->dropColumn(['certificate_media_id', 'expires_at', 'last_reminded_at']);
        });
    }
};
