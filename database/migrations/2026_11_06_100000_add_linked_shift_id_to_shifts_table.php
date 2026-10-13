<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 18: links two Shift rows together as one double-handed visit
     * (two carers, same service user and time window, each still getting
     * their own row). Deliberately NOT a second "assigned_to_2" column on
     * one row — keeping one carer per Shift row means RotaPeriod::
     * generateTimesheets() and every other per-carer query keep working
     * unchanged; this column only groups the two rows for display. Points
     * at the OTHER shift in the pair; set on both rows when the second
     * carer is added (see App\Models\Shift::linkWith()).
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignUuid('linked_shift_id')->nullable()->after('shift_type')
                ->constrained('shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['linked_shift_id']);
            $table->dropColumn('linked_shift_id');
        });
    }
};
