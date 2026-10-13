<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 18: adds 'live_in' to shifts.shift_type's allowed values — a
     * carer placed with a service user continuously for several days,
     * paid a flat daily rate rather than hourly day/night slots (see
     * App\Models\EmployeePayProfile::live_in_daily_rate and
     * RotaPeriod::generateTimesheets()). A live-in Shift's scheduled_start/
     * scheduled_end span the whole placement rather than one calendar
     * day, and it isn't shown in the weekly day/night rota grid — see
     * App\Livewire\Rota\LiveInPlacementComponent.
     *
     * Widening a MySQL ENUM's allowed values needs a raw ALTER (Laravel's
     * schema builder has no "add an enum value" helper, and redefining
     * the column via Schema::table()->enum() needs doctrine/dbal, which
     * isn't installed in this app — see the shift_takeovers migration's
     * doc comment for the same constraint). Column stays nullable, same
     * as today.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE shifts MODIFY shift_type ENUM('day', 'night', 'live_in') NULL");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // Any existing 'live_in' rows would fail this narrowing ALTER —
            // not expected to run down() with live-in shifts still in place.
            DB::statement("ALTER TABLE shifts MODIFY shift_type ENUM('day', 'night') NULL");
        }
    }
};
