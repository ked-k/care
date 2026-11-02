<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 44b: the written notes to the financial statements (accounting
 * policies, basis of preparation, events after the year end…), per
 * financial year. The numbers notes (fixed assets, receivables, payables,
 * cash, reserves) are generated from the ledger, not stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_report_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignUuid('financial_year_id')->constrained('acc_financial_years')->cascadeOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['financial_year_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_report_notes');
    }
};
