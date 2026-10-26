<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 39: "employment start date" is one of the client's requested
 * personal-details fields. employee_pay_profiles is already this app's
 * one-per-staff-member "employment" record (employee_no, job_title,
 * employment_type live here) — adding it there rather than starting a new
 * table for it, or bolting it onto the payroll-focused fields it already
 * has.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_pay_profiles', function (Blueprint $table) {
            $table->date('employment_start_date')->nullable()->after('job_title');
        });
    }

    public function down(): void
    {
        Schema::table('employee_pay_profiles', function (Blueprint $table) {
            $table->dropColumn('employment_start_date');
        });
    }
};
