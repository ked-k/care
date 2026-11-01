<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch 43: money in from a customer (receipt) or out to a supplier (payment). */
class Payment extends Model
{
    use HasUuids, Concerns\PlainDates;

    protected array $plainDates = ['date'];

    protected $table = 'acc_payments';

    protected $fillable = [
        'agency_id', 'kind', 'number', 'contact_id', 'bank_account_id', 'date', 'amount', 'method',
        'reference', 'notes', 'status', 'source_type', 'source_id', 'journal_id', 'created_by', 'voided_at',
    ];

    protected $casts = ['date' => 'date', 'amount' => 'decimal:2', 'voided_at' => 'datetime'];

    public const METHODS = [
        'bank_transfer' => 'Bank transfer',
        'cash' => 'Cash',
        'card' => 'Card',
        'cheque' => 'Cheque',
        'mobile_money' => 'Mobile money',
        'direct_debit' => 'Direct debit',
        'other' => 'Other',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocated(): float
    {
        return round((float) $this->allocations()->sum('amount'), 2);
    }

    public function unallocated(): float
    {
        return round((float) $this->amount - $this->allocated(), 2);
    }

    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    public function kindLabel(): string
    {
        return $this->kind === 'receipt' ? __('Customer receipt') : __('Supplier payment');
    }

    public function methodLabel(): string
    {
        return __(self::METHODS[$this->method] ?? ucfirst($this->method));
    }
}
