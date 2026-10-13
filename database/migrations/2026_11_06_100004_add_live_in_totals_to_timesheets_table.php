<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 18: weekly totals for live-in days, computed by
     * App\Models\Timesheet::recalculateTotals() alongside the existing
     * regular/overtime hour totals, kept in their own columns rather than
     * folded into total_regular_hours — a live-in day is paid a flat
     * rate, not an hourly amount, so it has no meaningful "hours worked"
     * to add to the overtime-threshold calculation.
     */
    public function up(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->unsignedSmallInteger('total_live_in_days')->default(0)->after('total_overtime_hours');
            $table->decimal('total_live_in_pay', 12, 2)->default(0)->after('total_live_in_days');
        });
    }

    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropColumn(['total_live_in_days', 'total_live_in_pay']);
        });
    }
};
