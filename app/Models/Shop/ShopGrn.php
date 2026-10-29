<?php

namespace App\Models\Shop;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Batch 42: a goods received note — one delivery from a supplier, many lines.
 * Draft until "Receive stock", which posts every line into stock as a lot
 * (see App\Services\Shop\GoodsReceiving). A received GRN can be reversed only
 * while none of its stock has been sold or moved.
 */
class ShopGrn extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_id', 'number', 'supplier_id', 'supplier_reference', 'purchase_order', 'received_on', 'status', 'notes',
        'total_cost', 'created_by', 'received_by', 'received_at', 'reversed_by', 'reversed_at', 'reversal_reason',
    ];

    protected $casts = [
        'received_on' => 'date',
        'received_at' => 'datetime',
        'reversed_at' => 'datetime',
        'total_cost' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopStore::class, 'store_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ShopSupplier::class, 'supplier_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ShopGrnLine::class, 'grn_id')->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function statusLabel(): string
    {
        return ['draft' => __('Draft'), 'received' => __('Received'), 'reversed' => __('Reversed')][$this->status] ?? ucfirst($this->status);
    }

    /** GRN-00001, from the store's own counter. */
    public static function nextNumber(ShopStore $store): string
    {
        return DB::transaction(function () use ($store) {
            $fresh = ShopStore::whereKey($store->id)->lockForUpdate()->first();
            $n = (int) $fresh->next_grn_number;
            $fresh->increment('next_grn_number');

            return 'GRN-'.str_pad((string) $n, 5, '0', STR_PAD_LEFT);
        });
    }
}
