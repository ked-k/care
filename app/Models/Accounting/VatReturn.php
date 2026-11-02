<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Batch 44c: a VAT return as filed. The box figures are frozen here; the
 * journals it picked up point back at it (acc_journals.vat_return_id).
 */
class VatReturn extends Model
{
    use HasUuids, Concerns\PlainDates;

    protected array $plainDates = ['period_start', 'period_end', 'due_date', 'paid_on'];

    protected $table = 'acc_vat_returns';

    protected $fillable = [
        'agency_id', 'period_start', 'period_end', 'due_date', 'status', 'boxes', 'journal_count',
        'reference', 'notes', 'filed_by', 'filed_at', 'payment_journal_id', 'paid_on',
    ];

    protected $casts = [
        'period_start' => 'date', 'period_end' => 'date', 'due_date' => 'date', 'paid_on' => 'date',
        'filed_at' => 'datetime', 'boxes' => 'array',
    ];

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function paymentJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'payment_journal_id');
    }

    public function filer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by');
    }

    public function net(): float
    {
        return round((float) ($this->boxes['box5'] ?? 0), 2);
    }

    public function label(): string
    {
        return $this->period_start->format('M Y').' – '.$this->period_end->format('M Y');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
