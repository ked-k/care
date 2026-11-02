<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch 44c: a budget for one financial year — a monthly figure per income / expense account. */
class Budget extends Model
{
    use HasUuids;

    protected $table = 'acc_budgets';

    protected $fillable = ['agency_id', 'financial_year_id', 'name', 'is_primary', 'notes', 'created_by'];

    protected $casts = ['is_primary' => 'boolean'];

    public function year(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
