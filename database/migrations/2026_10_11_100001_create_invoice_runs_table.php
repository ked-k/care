<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Batch 21 (Invoicing). Mirrors payroll_runs — a billing period that
 * groups the Invoice rows generated for every service user in the
 * agency, the same way a PayrollRun groups Payslips.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('reference');
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->enum('frequency', ['weekly', 'monthly'])->default('weekly');
            $table->enum('status', ['draft', 'issued', 'paid'])->default('draft');
            $table->decimal('total_billed', 12, 2)->default(0);
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['agency_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_runs');
    }
};
