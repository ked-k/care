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
        'agency_id', 'kind', 'number', 'contact_id', 'bank_account_id', 'issue_date', 'due_date', 'reference',
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
    ];

    public const STATUS_LABELS = [
        'draft' => 'Draft', 'posted' => 'Unpaid', 'partial' => 'Part paid', 'paid' => 'Paid', 'void' => 'Void',
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

        return __(self::STATUS_LABELS[$this->status] ?? ucfirst($this->status));
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
        return $this->isOpen() && $this->due_date && $this->due_date->isBefore(today());
    }

    public function balance(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }

    /** Invoices and bills sit on a contact's account; money in/out doesn't. */
    public function isOnAccount(): bool
    {
        return in_array($this->kind, ['invoice', 'bill'], true);
    }

    public function isSales(): bool
    {
        return in_array($this->kind, ['invoice', 'money_in'], true);
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
