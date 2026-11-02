<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 44c: one account's 12 monthly budget figures (month 1 = first month of the financial year). */
class BudgetLine extends Model
{
    use HasUuids;

    protected $table = 'acc_budget_lines';

    protected $fillable = ['budget_id', 'account_id', 'months', 'total'];

    protected $casts = ['months' => 'array', 'total' => 'decimal:2'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}
