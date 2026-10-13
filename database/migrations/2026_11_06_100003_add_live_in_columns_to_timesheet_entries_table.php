<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 18: one TimesheetEntry row per calendar day already exists
     * (see the day_shift_id/night_shift_id columns); a day that falls
     * inside a live-in placement gets live_in_shift_id + a snapshot of
     * the rate that applied that day instead — day_shift_id/
     * night_shift_id stay null for that row, and vice versa. Kept as a
     * flat per-day rate rather than hours, so it's added to the payslip
     * as a separate earning line (App\Models\Payslip::addEarning()) and
     * never mixed into the hourly regular/overtime calculation in
     * App\Models\Timesheet::recalculateTotals().
     */
    public function up(): void
    {
        Schema::table('timesheet_entries', function (Blueprint $table) {
            $table->uuid('live_in_shift_id')->nullable()->after('night_shift_end');
            $table->decimal('live_in_rate', 10, 2)->nullable()->after('live_in_shift_id');

            $table->foreign('live_in_shift_id')->references('id')->on('shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('timesheet_entries', function (Blueprint $table) {
            $table->dropForeign(['live_in_shift_id']);
            $table->dropColumn(['live_in_shift_id', 'live_in_rate']);
        });
    }
};
