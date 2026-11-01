<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 43: the Finance & Accounts workspace — a double-entry ledger per
 * agency. Every money event (a care invoice issued, a payroll run approved,
 * a shop sale, a goods-received note, a hand-entered invoice or bill) ends
 * up as a balanced journal in acc_journals / acc_journal_lines, and every
 * report reads only from those two tables.
 *
 * Posted journals are never edited: a mistake is put right by a reversing
 * journal (Journal::reverse()), so the audit trail stays complete.
 */
return new class extends Migration
{
    public function up(): void
    {
        // One row per agency: currency, financial-year start, numbering,
        // VAT, which events post automatically, and the default accounts.
        Schema::create('acc_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->unique()->constrained('agencies')->cascadeOnDelete();
            $table->string('legal_name')->nullable();
            $table->string('registration_number', 80)->nullable();
            $table->string('tax_number', 80)->nullable();
            $table->text('address')->nullable();
            // company | cooperative | charity | sole_trader — decides the
            // wording on statements ("Profit" vs "Surplus", "Income" vs "Revenue").
            $table->string('organisation_type', 20)->default('company');
            $table->string('currency_code', 3)->default('GBP');
            $table->string('currency_symbol', 8)->default('£');
            $table->unsignedTinyInteger('currency_decimals')->default(2);
            $table->unsignedTinyInteger('fy_start_month')->default(4);
            $table->unsignedTinyInteger('fy_start_day')->default(1);
            $table->date('lock_date')->nullable();
            // Automatic posting only picks up records on or after this date —
            // anything older belongs in the opening balances.
            $table->date('posting_from')->nullable();
            $table->boolean('vat_registered')->default(false);
            $table->decimal('default_vat_rate', 5, 2)->default(0);
            $table->unsignedSmallInteger('payment_terms_days')->default(30);
            $table->json('numbering')->nullable();   // prefix + next number per document kind
            $table->json('auto_post')->nullable();   // care_invoices, payroll, shop, stock => bool
            $table->json('accounts')->nullable();    // overrides of the system-key accounts
            $table->json('appropriations')->nullable(); // Batch 44: reserves etc. at year end
            $table->text('invoice_footer')->nullable();
            $table->timestamps();
        });

        Schema::create('acc_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('type', 12);     // asset | liability | equity | income | expense
            $table->string('subtype', 30);  // see App\Models\Accounting\Account::SUBTYPES
            $table->string('system_key', 40)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            // Bank / cash / mobile-money details (subtype bank|cash|mobile_money).
            $table->string('bank_name')->nullable();
            $table->string('account_number', 60)->nullable();
            $table->string('sort_code', 30)->nullable();
            $table->string('external_ref', 60)->nullable(); // e.g. "erpgo:51"
            $table->timestamps();

            $table->unique(['agency_id', 'code']);
            $table->unique(['agency_id', 'system_key']);
            $table->index(['agency_id', 'type']);
        });

        Schema::create('acc_financial_years', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('name', 40);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 10)->default('open'); // open | closed
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['agency_id', 'starts_on']);
        });

        // Customers and suppliers in one table (a contact can be both).
        Schema::create('acc_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->boolean('is_customer')->default(false);
            $table->boolean('is_supplier')->default(false);
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->string('tax_number', 60)->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->nullable();
            $table->uuid('service_user_id')->nullable();   // a care client who pays their own bills
            $table->string('payer_key', 120)->nullable();  // care invoicing: "self:<su>", "la:<name>"
            $table->uuid('shop_supplier_id')->nullable();
            $table->string('external_ref', 60)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['agency_id', 'name']);
            $table->unique(['agency_id', 'payer_key']);
            $table->index('service_user_id');
            $table->index('shop_supplier_id');
        });

        Schema::create('acc_journals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignUuid('financial_year_id')->nullable()->constrained('acc_financial_years')->nullOnDelete();
            $table->string('number', 30);
            $table->date('date');
            $table->string('type', 20); // see Journal::TYPES
            $table->string('reference')->nullable();
            $table->text('memo')->nullable();
            // What created it — idempotent automatic posting keys on these three.
            $table->string('source_type', 40)->nullable();
            $table->string('source_id', 40)->nullable();
            $table->string('source_event', 20)->nullable();
            $table->decimal('total', 16, 2)->default(0);
            $table->string('status', 10)->default('posted'); // posted | reversed
            $table->uuid('reverses_id')->nullable();
            $table->uuid('reversed_by_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['agency_id', 'number']);
            $table->unique(['agency_id', 'source_type', 'source_id', 'source_event'], 'acc_journals_source_unique');
            $table->index(['agency_id', 'date']);
        });

        Schema::create('acc_journal_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('journal_id')->constrained('acc_journals')->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained('acc_accounts')->restrictOnDelete();
            $table->foreignUuid('contact_id')->nullable()->constrained('acc_contacts')->nullOnDelete();
            // Denormalised so reports never need the join.
            $table->uuid('agency_id');
            $table->date('date');
            $table->string('description')->nullable();
            $table->decimal('debit', 16, 2)->default(0);
            $table->decimal('credit', 16, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['agency_id', 'account_id', 'date']);
            $table->index(['contact_id', 'date']);
        });

        // Sales invoices, supplier bills and one-off "money in / money out"
        // (spend or receive money straight from a bank account).
        Schema::create('acc_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('kind', 12); // invoice | bill | money_in | money_out
            $table->string('number', 40);
            $table->foreignUuid('contact_id')->nullable()->constrained('acc_contacts')->nullOnDelete();
            $table->foreignUuid('bank_account_id')->nullable()->constrained('acc_accounts')->nullOnDelete(); // money in/out only
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('reference')->nullable();
            $table->string('status', 10)->default('draft'); // draft | posted | partial | paid | void
            $table->boolean('prices_include_vat')->default(false);
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('vat_total', 16, 2)->default(0);
            $table->decimal('total', 16, 2)->default(0);
            $table->decimal('amount_paid', 16, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('source_type', 40)->nullable(); // care_invoice | shop_grn | erpgo
            $table->string('source_id', 40)->nullable();
            $table->foreignUuid('journal_id')->nullable()->constrained('acc_journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->unique(['agency_id', 'kind', 'number']);
            $table->index(['agency_id', 'kind', 'status']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('acc_document_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained('acc_documents')->cascadeOnDelete();
            $table->foreignUuid('account_id')->nullable()->constrained('acc_accounts')->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('qty', 12, 3)->default(1);
            $table->decimal('unit_price', 16, 4)->default(0);
            $table->decimal('discount', 16, 2)->default(0); // an amount off the line
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('net', 16, 2)->default(0);
            $table->decimal('vat', 16, 2)->default(0);
            $table->decimal('total', 16, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Money received from customers (receipt) or paid to suppliers (payment).
        Schema::create('acc_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('kind', 10); // receipt | payment
            $table->string('number', 40);
            $table->foreignUuid('contact_id')->nullable()->constrained('acc_contacts')->nullOnDelete();
            $table->foreignUuid('bank_account_id')->constrained('acc_accounts')->restrictOnDelete();
            $table->date('date');
            $table->decimal('amount', 16, 2);
            $table->string('method', 20)->default('bank_transfer');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 10)->default('posted'); // posted | void
            $table->string('source_type', 40)->nullable();
            $table->string('source_id', 40)->nullable();
            $table->foreignUuid('journal_id')->nullable()->constrained('acc_journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->unique(['agency_id', 'kind', 'number']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('acc_payment_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_id')->constrained('acc_payments')->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained('acc_documents')->cascadeOnDelete();
            $table->decimal('amount', 16, 2);
            $table->timestamps();

            $table->unique(['payment_id', 'document_id']);
        });

        // Automatic posting never blocks the care/payroll/shop action that
        // triggered it. When it can't post (books locked, account archived…)
        // the reason is kept here and shown on the Finance dashboard to retry.
        Schema::create('acc_posting_issues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id')->nullable()->index();
            $table->string('source_type', 40);
            $table->string('source_id', 40);
            $table->string('label')->nullable();
            $table->text('message');
            $table->unsignedInteger('attempts')->default(1);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acc_posting_issues');
        Schema::dropIfExists('acc_payment_allocations');
        Schema::dropIfExists('acc_payments');
        Schema::dropIfExists('acc_document_lines');
        Schema::dropIfExists('acc_documents');
        Schema::dropIfExists('acc_journal_lines');
        Schema::dropIfExists('acc_journals');
        Schema::dropIfExists('acc_contacts');
        Schema::dropIfExists('acc_financial_years');
        Schema::dropIfExists('acc_accounts');
        Schema::dropIfExists('acc_settings');
    }
};
