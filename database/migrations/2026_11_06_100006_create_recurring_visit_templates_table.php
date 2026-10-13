<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 18: "set a repeating pattern once" instead of re-entering the
     * same visit every week — see App\Models\RecurringVisitTemplate. One
     * template can generate either real day/night Shift rows (payroll) or
     * CareCall welfare-check rows (non-payroll); template_type picks
     * which, and only the columns that type needs are used (shift_type/
     * break_minutes for a shift template, call_label for a care_call
     * one). Generation is explicit and idempotent (App\Models\
     * RecurringVisitTemplate::generate()) — nothing is created on a
     * schedule, same "no cron in this app" reasoning as
     * App\Support\AlertGenerator and CareCallStatus.
     */
    public function up(): void
    {
        Schema::create('recurring_visit_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('service_user_id')->constrained('service_users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('template_type'); // shift, care_call
            $table->string('shift_type')->nullable(); // day, night — shift templates only
            $table->string('call_label')->nullable(); // care_call templates only
            $table->json('days_of_week'); // [0..6], 0 = Sunday, matching Carbon::dayOfWeek
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->date('starts_on');
            $table->date('ends_on')->nullable(); // null = indefinite
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamps();

            $table->index(['agency_id', 'is_active']);
            $table->index(['service_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_visit_templates');
    }
};
