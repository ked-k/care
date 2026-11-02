<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 44c: part of a credit note used against an invoice (or a supplier credit against a bill). */
class CreditAllocation extends Model
{
    use HasUuids, Concerns\PlainDates;

    protected array $plainDates = ['date'];

    protected $table = 'acc_credit_allocations';

    protected $fillable = ['agency_id', 'credit_note_id', 'document_id', 'amount', 'date', 'created_by'];

    protected $casts = ['amount' => 'decimal:2', 'date' => 'date'];

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'credit_note_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
