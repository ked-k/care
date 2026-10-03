<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records a manager/admin stepping in to cover a carer's shift —
     * "take over any carer's session" — for whenever the carer couldn't
     * (or didn't) complete their check-in, tasks, medications or notes
     * themselves. See App\Models\ShiftTakeover.
     */
    public function up(): void
    {
        Schema::create('shift_takeovers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('carer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();

            $table->index(['shift_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_takeovers');
    }
};
