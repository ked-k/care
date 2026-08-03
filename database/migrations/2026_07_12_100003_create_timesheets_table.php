<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timesheets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('agency_id', 36);
            $table->unsignedBigInteger('user_id'); // the carer/employee this timesheet belongs to
            $table->uuid('rota_period_id')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();

            $table->date('week_commencing');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'paid'])->default('draft');

            $table->decimal('total_regular_hours', 6, 2)->default(0);
            $table->decimal('total_overtime_hours', 6, 2)->default(0);

            $table->text('comments')->nullable();
            $table->timestamp('employee_signed_at')->nullable();
            $table->timestamp('supervisor_signed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['agency_id', 'user_id', 'week_commencing']);

            $table->foreign('agency_id')->references('id')->on('agencies')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('rota_period_id')->references('id')->on('rota_periods')->nullOnDelete();
            $table->foreign('manager_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('updated_by')->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};
