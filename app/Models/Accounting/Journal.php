<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Batch 43: one balanced double-entry posting. Never edited once saved —
 * App\Services\Accounting\Ledger::reverse() posts the mirror image instead.
 */
class Journal extends Model
{
    use HasUuids, Concerns\PlainDates;

    protected array $plainDates = ['date'];

    protected $table = 'acc_journals';

    protected $fillable = [
        'agency_id', 'financial_year_id', 'number', 'date', 'type', 'reference', 'memo',
        'source_type', 'source_id', 'source_event', 'total', 'status', 'reverses_id', 'reversed_by_id', 'created_by',
    ];

    protected $casts = ['date' => 'date', 'total' => 'decimal:2'];

    public const TYPES = [
        'manual' => 'Journal entry',
        'opening' => 'Opening balances',
        'invoice' => 'Sales invoice',
        'bill' => 'Supplier bill',
        'money_in' => 'Money in',
        'money_out' => 'Money out',
        'receipt' => 'Customer receipt',
        'payment' => 'Supplier payment',
        'transfer' => 'Bank transfer',
        'payroll' => 'Payroll',
        'shop_sale' => 'Shop sale',
        'stock' => 'Stock movement',
        'grn' => 'Goods received',
        'reversal' => 'Reversal',
        'year_end' => 'Year-end close',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_by_id');
    }

    public function typeLabel(): string
    {
        return __(self::TYPES[$this->type] ?? ucfirst($this->type));
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    /** Where the user can see the record this journal came from, if anywhere. */
    public function sourceUrl(): ?string
    {
        if (! $this->source_type || ! $this->source_id) {
            return null;
        }

        return match ($this->source_type) {
            'document' => route('accounts.documents.show', $this->source_id),
            'payment' => route('accounts.payments.show', $this->source_id),
            'payroll_run' => \Illuminate\Support\Facades\Route::has('payroll.show') ? route('payroll.show', $this->source_id) : null,
            'shop_order' => \Illuminate\Support\Facades\Route::has('shop.admin.orders.show') ? route('shop.admin.orders.show', $this->source_id) : null,
            'shop_grn' => \Illuminate\Support\Facades\Route::has('shop.admin.receiving.show') ? route('shop.admin.receiving.show', $this->source_id) : null,
            default => null,
        };
    }
}
