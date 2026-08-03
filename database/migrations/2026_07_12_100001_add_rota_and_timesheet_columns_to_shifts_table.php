<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Groups shifts into a weekly rota (nullable so ungrouped/ad-hoc shifts still work).
            $table->uuid('rota_period_id')->nullable()->after('agency_id');
            // Drives which timesheet column (Day Shift / Night Shift) a shift lands in.
            $table->enum('shift_type', ['day', 'night'])->nullable()->after('scheduled_end');
            $table->unsignedSmallInteger('break_minutes')->default(0)->after('shift_type');

            $table->foreign('rota_period_id')->references('id')->on('rota_periods')->nullOnDelete();
            $table->index(['rota_period_id', 'shift_type']);
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['rota_period_id']);
            $table->dropColumn(['rota_period_id', 'shift_type', 'break_minutes']);
        });
    }
};
