<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payslip_id');
            $table->enum('line_type', ['earning', 'deduction']);
            $table->string('category'); // overtime, bonus, allowance, paye, nssf, loan_repayment, other
            $table->string('description')->nullable();
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->foreign('payslip_id')->references('id')->on('payslips')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_lines');
    }
};
