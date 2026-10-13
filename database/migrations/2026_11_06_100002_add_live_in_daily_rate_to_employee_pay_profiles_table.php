<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 18: the flat rate paid per live-in day for this carer — set
     * by a manager when placing them on a live-in shift. Nullable: a
     * carer never placed live-in simply never has one, and
     * RotaPeriod::generateTimesheets() flags the gap rather than guessing
     * a rate if a live-in shift is created before this is set.
     */
    public function up(): void
    {
        Schema::table('employee_pay_profiles', function (Blueprint $table) {
            $table->decimal('live_in_daily_rate', 10, 2)->nullable()->after('weekly_overtime_threshold_hours');
        });
    }

    public function down(): void
    {
        Schema::table('employee_pay_profiles', function (Blueprint $table) {
            $table->dropColumn('live_in_daily_rate');
        });
    }
};
