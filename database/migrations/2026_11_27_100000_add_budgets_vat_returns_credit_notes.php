<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 44c: budgets (a monthly figure per income / expense account for a
 * financial year), VAT returns worked out from the ledger, and credit
 * notes against sales invoices and supplier bills.
 */
return new class extends Migration
{
    public function up(): void
    {
        // -- Credit notes ---------------------------------------------------
        // Credit notes live in acc_documents (kind credit_note | bill_credit).
        Schema::table('acc_documents', function (Blueprint $table) {
            $table->foreignUuid('credited_document_id')->nullable()->after('contact_id')->constrained('acc_documents')->nullOnDelete();
        });

        // A credit note's value used against an invoice (or a supplier credit
        // against a bill). Like payment allocations, this doesn't touch the
        // ledger — both documents already sit on the contact's account.
        Schema::create('acc_credit_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignUuid('credit_note_id')->constrained('acc_documents')->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained('acc_documents')->cascadeOnDelete();
            $table->decimal('amount', 16, 2);
            $table->date('date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('credit_note_id');
            $table->index('document_id');
        });

        // -- VAT returns ----------------------------------------------------
        Schema::create('acc_vat_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_date')->nullable();
            $table->string('status', 10)->default('filed'); // filed | paid
            $table->json('boxes');                            // box1 … box9 as filed
            $table->unsignedInteger('journal_count')->default(0);
            $table->string('reference', 80)->nullable();      // HMRC / revenue authority receipt
            $table->text('notes')->nullable();
            $table->foreignId('filed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('filed_at')->nullable();
            $table->foreignUuid('payment_journal_id')->nullable()->constrained('acc_journals')->nullOnDelete();
            $table->date('paid_on')->nullable();
            $table->timestamps();

            $table->unique(['agency_id', 'period_start']);
        });

        // Which return picked each journal up, so an entry dated in a period
        // that's already been filed goes on the next return instead of being missed.
        Schema::table('acc_journals', function (Blueprint $table) {
            $table->foreignUuid('vat_return_id')->nullable()->constrained('acc_vat_returns')->nullOnDelete();
        });

        Schema::table('acc_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('vat_period_months')->default(3);    // 1 | 3 | 12
            $table->unsignedTinyInteger('vat_period_end_month')->default(3); // a month a period ends in
            $table->date('vat_start_date')->nullable();                       // first day of the first return
        });

        // -- Budgets ----------------------------------------------------------
        Schema::create('acc_budgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignUuid('financial_year_id')->constrained('acc_financial_years')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_primary')->default(false); // the one reports use by default
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agency_id', 'financial_year_id']);
        });

        Schema::create('acc_budget_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('budget_id')->constrained('acc_budgets')->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained('acc_accounts')->cascadeOnDelete();
            $table->json('months');                    // 12 figures, month 1 = first month of the year
            $table->decimal('total', 16, 2)->default(0);
            $table->timestamps();

            $table->unique(['budget_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_budget_lines');
        Schema::dropIfExists('acc_budgets');
        Schema::table('acc_settings', fn (Blueprint $t) => $t->dropColumn(['vat_period_months', 'vat_period_end_month', 'vat_start_date']));
        Schema::table('acc_journals', function (Blueprint $t) {
            $t->dropConstrainedForeignId('vat_return_id');
        });
        Schema::dropIfExists('acc_vat_returns');
        Schema::dropIfExists('acc_credit_allocations');
        Schema::table('acc_documents', function (Blueprint $t) {
            $t->dropConstrainedForeignId('credited_document_id');
        });
    }
};
