<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Batch 21 (Invoicing). Mirrors payslip_lines. 'visit' lines back-
 * reference the Shift they bill for; 'package' lines are the flat
 * recurring charge; 'adjustment' lines are manual additions (including
 * the automatic split-netting line InvoiceRun::generateInvoices() adds
 * for a service user billed partly by the local authority).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->enum('line_type', ['visit', 'package', 'adjustment']);
            $table->foreignUuid('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 6, 2)->nullable();
            $table->decimal('rate', 8, 2)->nullable();
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
