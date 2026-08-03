<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timesheet_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('timesheet_id');

            $table->date('entry_date');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);

            // Traceability back to the originating shift(s) this entry was generated/reconciled from.
            $table->uuid('day_shift_id')->nullable();
            $table->time('day_shift_start')->nullable();
            $table->time('day_shift_end')->nullable();

            $table->uuid('night_shift_id')->nullable();
            $table->time('night_shift_start')->nullable();
            $table->time('night_shift_end')->nullable();

            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->decimal('total_hours', 5, 2)->default(0);

            $table->char('service_user_id', 36)->nullable();
            $table->string('initials')->nullable();

            $table->timestamps();

            $table->unique(['timesheet_id', 'entry_date']);

            $table->foreign('timesheet_id')->references('id')->on('timesheets')->cascadeOnDelete();
            $table->foreign('day_shift_id')->references('id')->on('shifts')->nullOnDelete();
            $table->foreign('night_shift_id')->references('id')->on('shifts')->nullOnDelete();
            $table->foreign('service_user_id')->references('id')->on('service_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheet_entries');
    }
};
