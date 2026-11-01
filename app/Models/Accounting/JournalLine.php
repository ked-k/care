<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    use HasUuids, Concerns\PlainDates;

    protected array $plainDates = ['date'];

    protected $table = 'acc_journal_lines';

    protected $fillable = ['journal_id', 'account_id', 'contact_id', 'agency_id', 'date', 'description', 'debit', 'credit', 'sort_order'];

    protected $casts = ['date' => 'date', 'debit' => 'decimal:2', 'credit' => 'decimal:2'];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
