<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Batch 43: a sales invoice, a supplier bill, or a one-off "money in" /
 * "money out" straight through a bank account. Drafts are editable; once
 * posted the lines are frozen and the only way back is Void (which
 * reverses its journal).
 */
class Document extends Model
{
    use HasUuids, Concerns\PlainDates;

    protected array $plainDates = ['issue_date', 'due_date'];

    protected $table = 'acc_documents';

    protected $fillable = [
        'agency_id', 'kind', 'number', 'contact_id', 'credited_document_id', 'bank_account_id', 'issue_date', 'due_date', 'reference',
        'status', 'prices_include_vat', 'subtotal', 'vat_total', 'total', 'amount_paid', 'notes',
        'source_type', 'source_id', 'journal_id', 'created_by', 'posted_at', 'voided_at', 'void_reason',
    ];

    protected $casts = [
        'issue_date' => 'date', 'due_date' => 'date', 'posted_at' => 'datetime', 'voided_at' => 'datetime',
        'prices_include_vat' => 'boolean',
        'subtotal' => 'decimal:2', 'vat_total' => 'decimal:2', 'total' => 'decimal:2', 'amount_paid' => 'decimal:2',
    ];

    public const KINDS = [
        'invoice' => ['Sales invoice', 'Sales invoices', 'ik ik-file-text'],
        'bill' => ['Supplier bill', 'Supplier bills', 'ik ik-file-minus'],
        'money_in' => ['Money in', 'Money in', 'ik ik-download'],
        'money_out' => ['Money out', 'Money out', 'ik ik-upload'],
        // Batch 44c
        'credit_note' => ['Credit note', 'Credit notes', 'ik ik-corner-up-left'],
        'bill_credit' => ['Supplier credit note', 'Supplier credit notes', 'ik ik-corner-down-right'],
    ];

    /** The kind of document a credit kind cancels out, and back again. */
    public const CREDIT_FOR = ['credit_note' => 'invoice', 'bill_credit' => 'bill'];

    public const STATUS_LABELS = [
        'draft' => 'Draft', 'posted' => 'Unpaid', 'partial' => 'Part paid', 'paid' => 'Paid', 'void' => 'Void',
    ];

    /** A credit note's statuses read differently: "paid" means fully used. */
    public const CREDIT_STATUS_LABELS = [
        'draft' => 'Draft', 'posted' => 'Not yet used', 'partial' => 'Part used', 'paid' => 'Used', 'void' => 'Void',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(DocumentLine::class)->orderBy('sort_order');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /** Batch 44c: the invoice / bill a credit note was raised against. */
    public function credited(): BelongsTo
    {
        return $this->belongsTo(self::class, 'credited_document_id');
    }

    /** Credit notes raised against this invoice / bill. */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(self::class, 'credited_document_id');
    }

    /** As a credit note: where its value has been used. */
    public function creditUses(): HasMany
    {
        return $this->hasMany(CreditAllocation::class, 'credit_note_id');
    }

    /** As an invoice / bill: credit notes used against it. */
    public function creditsApplied(): HasMany
    {
        return $this->hasMany(CreditAllocation::class, 'document_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kindLabel(): string
    {
        return __(self::KINDS[$this->kind][0] ?? $this->kind);
    }

    public function statusLabel(): string
    {
        if ($this->isOverdue()) {
            return __('Overdue');
        }

        return __(($this->isCredit() ? self::CREDIT_STATUS_LABELS : self::STATUS_LABELS)[$this->status] ?? ucfirst($this->status));
    }

    public function statusColor(): string
    {
        if ($this->isOverdue()) {
            return 'danger';
        }

        return match ($this->status) {
            'paid' => 'success', 'partial' => 'warning', 'posted' => 'primary', 'void' => 'dark', default => 'gray',
        };
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['posted', 'partial'], true);
    }

    public function isOverdue(): bool
    {
        return ! $this->isCredit() && $this->isOpen() && $this->due_date && $this->due_date->isBefore(today());
    }

    public function balance(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }

    /** Invoices, bills and credit notes sit on a contact's account; money in/out doesn't. */
    public function isOnAccount(): bool
    {
        return in_array($this->kind, ['invoice', 'bill', 'credit_note', 'bill_credit'], true);
    }

    /** Customer-side documents (invoices, credit notes, money in). */
    public function isSales(): bool
    {
        return in_array($this->kind, ['invoice', 'money_in', 'credit_note'], true);
    }

    /** Batch 44c: a credit note (sales) or supplier credit note. */
    public function isCredit(): bool
    {
        return isset(self::CREDIT_FOR[$this->kind]);
    }

    /**
     * Which way the document posts: invoices and money in credit income,
     * bills and money out debit expenses; a credit note posts the other way
     * to what it cancels.
     */
    public function postsAsIncome(): bool
    {
        return in_array($this->kind, ['invoice', 'money_in', 'bill_credit'], true);
    }

    /** The receivables/payables side: customer documents vs supplier ones. */
    public function isCustomerSide(): bool
    {
        return in_array($this->kind, ['invoice', 'credit_note', 'money_in'], true);
    }

    /** The list page a document belongs to. */
    public function listRoute(): string
    {
        return match ($this->kind) {
            'invoice' => 'accounts.invoices', 'bill' => 'accounts.bills',
            'credit_note' => 'accounts.credit-notes',
            'bill_credit' => 'accounts.supplier-credits',
            default => 'accounts.money',
        };
    }

    public function sourceLabel(): ?string
    {
        return match ($this->source_type) {
            'care_invoice' => __('Care invoicing'),
            'shop_grn' => __('Shop delivery (GRN)'),
            'erpgo' => __('Imported from the old ERP'),
            default => null,
        };
    }
}
