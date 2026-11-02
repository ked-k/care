<?php

namespace App\Models\Accounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch 44a: one item on the fixed assets register. */
class FixedAsset extends Model
{
    use HasUuids, Concerns\PlainDates;

    protected array $plainDates = ['purchase_date', 'depreciation_start', 'depreciated_to', 'disposed_on'];

    protected $table = 'acc_fixed_assets';

    protected $fillable = [
        'agency_id', 'number', 'name', 'category', 'serial_number', 'location',
        'asset_account_id', 'accumulated_account_id', 'expense_account_id',
        'purchase_date', 'cost', 'residual_value', 'method', 'rate', 'depreciation_start', 'opening_depreciation',
        'depreciated_to', 'status', 'disposed_on', 'disposal_proceeds', 'purchase_journal_id', 'disposal_journal_id',
        'notes', 'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date', 'depreciation_start' => 'date', 'depreciated_to' => 'date', 'disposed_on' => 'date',
        'cost' => 'decimal:2', 'residual_value' => 'decimal:2', 'rate' => 'decimal:2', 'opening_depreciation' => 'decimal:2',
        'disposal_proceeds' => 'decimal:2',
    ];

    /**
     * Category => [label, default method, default % a year, asset code, accumulated code].
     * Rates follow common practice (and the audited co-operative accounts:
     * computers and vehicles 25%, furniture 12.5%) — each can be changed.
     */
    public const CATEGORIES = [
        'land_buildings' => ['Land and buildings', 'straight_line', 2, '1810', '1815'],
        'motor_vehicles' => ['Motor vehicles', 'reducing_balance', 25, '1840', '1845'],
        'furniture' => ['Furniture and fittings', 'reducing_balance', 12.5, '1820', '1825'],
        'computers' => ['Computers and IT equipment', 'straight_line', 25, '1830', '1835'],
        'equipment' => ['Equipment and machinery', 'reducing_balance', 12.5, '1850', '1855'],
        'other' => ['Other assets', 'straight_line', 20, '1860', '1865'],
    ];

    public const METHODS = [
        'straight_line' => 'Straight line (same amount each year)',
        'reducing_balance' => 'Reducing balance (% of what’s left)',
        'none' => 'Not depreciated (e.g. land)',
    ];

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function accumulatedAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(DepreciationEntry::class, 'asset_id')->orderBy('period_end');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categoryLabel(): string
    {
        return __(self::CATEGORIES[$this->category][0] ?? ucfirst($this->category));
    }

    public function methodLabel(): string
    {
        return $this->method === 'none' ? __('Not depreciated')
            : __(':r% a year, :m', ['r' => rtrim(rtrim(number_format((float) $this->rate, 2), '0'), '.'), 'm' => $this->method === 'straight_line' ? __('straight line') : __('reducing balance')]);
    }

    /** Depreciation charged so far: brought-in figure plus every posted month (up to a date if given). */
    public function accumulated(?string $upTo = null): float
    {
        $posted = $this->entries()->when($upTo, fn ($q) => $q->where('period_end', '<=', $upTo))->sum('amount');

        return round((float) $this->opening_depreciation + (float) $posted, 2);
    }

    public function bookValue(?string $upTo = null): float
    {
        return round((float) $this->cost - $this->accumulated($upTo), 2);
    }

    public function isDisposed(): bool
    {
        return $this->status === 'disposed';
    }
}
