<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepreciationEntry extends Model
{
    use HasUuids, Concerns\PlainDates;

    protected array $plainDates = ['period_end'];

    protected $table = 'acc_depreciation_entries';

    protected $fillable = ['asset_id', 'journal_id', 'period_end', 'amount'];

    protected $casts = ['period_end' => 'date', 'amount' => 'decimal:2'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
