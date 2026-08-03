<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_pay_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('agency_id', 36);
            $table->unsignedBigInteger('user_id')->unique(); // one pay profile per carer/staff user
            $table->unsignedBigInteger('manager_id')->nullable();

            $table->string('employee_no');
            $table->string('job_title')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'casual', 'bank'])->default('casual');

            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('overtime_multiplier', 4, 2)->default(1.5);
            $table->decimal('weekly_overtime_threshold_hours', 5, 2)->default(40);
            $table->enum('pay_frequency', ['weekly', 'biweekly', 'monthly'])->default('weekly');

            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('mobile_money_number')->nullable();

            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['agency_id', 'employee_no']);

            $table->foreign('agency_id')->references('id')->on('agencies')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('manager_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('updated_by')->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_pay_profiles');
    }
};
