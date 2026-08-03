<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_run_id');
            $table->unsignedBigInteger('user_id');
            $table->uuid('timesheet_id')->nullable();

            $table->decimal('regular_hours', 6, 2)->default(0);
            $table->decimal('overtime_hours', 6, 2)->default(0);
            $table->decimal('regular_rate', 10, 2)->default(0);
            $table->decimal('overtime_rate', 10, 2)->default(0);

            $table->decimal('gross_pay', 14, 2)->default(0);
            $table->decimal('total_earnings_other', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('net_pay', 14, 2)->default(0);

            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->enum('payment_method', ['bank_transfer', 'mobile_money', 'cash'])->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->unique(['payroll_run_id', 'user_id']);

            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('timesheet_id')->references('id')->on('timesheets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
