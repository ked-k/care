<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch 44a: one bank statement agreed against the ledger. */
class Reconciliation extends Model
{
    use HasUuids, Concerns\PlainDates;

    protected array $plainDates = ['statement_date'];

    protected $table = 'acc_reconciliations';

    protected $fillable = ['agency_id', 'account_id', 'statement_date', 'statement_balance', 'status', 'source_file', 'notes', 'created_by', 'completed_by', 'completed_at'];

    protected $casts = ['statement_date' => 'date', 'statement_balance' => 'decimal:2', 'completed_at' => 'datetime'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StatementLine::class)->orderBy('date')->orderBy('sort_order');
    }

    public function ticked(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'reconciliation_id');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
