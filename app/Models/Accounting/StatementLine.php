<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 44a: one line from an imported bank statement. Amount: + in, − out. */
class StatementLine extends Model
{
    use HasUuids, Concerns\PlainDates;

    protected array $plainDates = ['date'];

    protected $table = 'acc_statement_lines';

    protected $fillable = ['reconciliation_id', 'date', 'description', 'reference', 'amount', 'journal_line_id', 'status', 'sort_order'];

    protected $casts = ['date' => 'date', 'amount' => 'decimal:2'];

    public function journalLine(): BelongsTo
    {
        return $this->belongsTo(JournalLine::class);
    }
}
