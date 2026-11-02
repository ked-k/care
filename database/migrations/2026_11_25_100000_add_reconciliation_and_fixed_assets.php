<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 44a: bank reconciliation, the fixed assets register with
 * depreciation, and (no new tables needed) the year-end close.
 */
return new class extends Migration
{
    public function up(): void
    {
        // One reconciliation = one bank statement, up to a date, agreed to its closing balance.
        Schema::create('acc_reconciliations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained('acc_accounts')->cascadeOnDelete();
            $table->date('statement_date');
            $table->decimal('statement_balance', 16, 2);
            $table->string('status', 12)->default('in_progress'); // in_progress | completed
            $table->string('source_file')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status']);
        });

        // Lines imported from the bank's CSV, matched to ledger lines.
        Schema::create('acc_statement_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reconciliation_id')->constrained('acc_reconciliations')->cascadeOnDelete();
            $table->date('date');
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount', 16, 2); // + money in, − money out
            $table->uuid('journal_line_id')->nullable();
            $table->string('status', 10)->default('unmatched'); // unmatched | matched | created | ignored
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('journal_line_id');
        });

        Schema::table('acc_journal_lines', function (Blueprint $table) {
            // Ticked off against a bank statement (cleared). Set while a
            // reconciliation is in progress; locked once it's completed.
            $table->uuid('reconciliation_id')->nullable()->after('contact_id');
            $table->index('reconciliation_id');
        });

        Schema::create('acc_fixed_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('number', 30);
            $table->string('name');
            $table->string('category', 40);
            $table->string('serial_number')->nullable();
            $table->string('location')->nullable();
            $table->foreignUuid('asset_account_id')->constrained('acc_accounts')->restrictOnDelete();
            $table->foreignUuid('accumulated_account_id')->constrained('acc_accounts')->restrictOnDelete();
            $table->foreignUuid('expense_account_id')->constrained('acc_accounts')->restrictOnDelete();
            $table->date('purchase_date');
            $table->decimal('cost', 16, 2);
            $table->decimal('residual_value', 16, 2)->default(0);
            $table->string('method', 20)->default('straight_line'); // straight_line | reducing_balance | none
            $table->decimal('rate', 6, 2)->default(0); // % a year
            $table->date('depreciation_start');
            // Depreciation charged before it came into these books (opening balances).
            $table->decimal('opening_depreciation', 16, 2)->default(0);
            $table->date('depreciated_to')->nullable(); // last month-end charged
            $table->string('status', 10)->default('active'); // active | disposed
            $table->date('disposed_on')->nullable();
            $table->decimal('disposal_proceeds', 16, 2)->nullable();
            $table->foreignUuid('purchase_journal_id')->nullable()->constrained('acc_journals')->nullOnDelete();
            $table->foreignUuid('disposal_journal_id')->nullable()->constrained('acc_journals')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['agency_id', 'number']);
        });

        // Each month's charge per asset, so the schedule and the ledger always agree.
        Schema::create('acc_depreciation_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('asset_id')->constrained('acc_fixed_assets')->cascadeOnDelete();
            $table->foreignUuid('journal_id')->constrained('acc_journals')->cascadeOnDelete();
            $table->date('period_end');
            $table->decimal('amount', 16, 2);
            $table->timestamps();

            $table->unique(['asset_id', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_depreciation_entries');
        Schema::dropIfExists('acc_fixed_assets');
        Schema::table('acc_journal_lines', function (Blueprint $table) {
            $table->dropIndex(['reconciliation_id']);
            $table->dropColumn('reconciliation_id');
        });
        Schema::dropIfExists('acc_statement_lines');
        Schema::dropIfExists('acc_reconciliations');
    }
};
