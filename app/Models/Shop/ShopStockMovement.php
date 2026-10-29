<?php

namespace App\Models\Shop;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The stock ledger — every change to a product's stock_qty, with why. */
class ShopStockMovement extends Model
{
    use HasUuids;

    protected $fillable = ['product_id', 'variant_id', 'grn_id', 'qty', 'type', 'reference', 'order_id', 'note', 'balance_after', 'user_id'];

    public const TYPE_LABELS = [
        'receipt' => 'Stock received',
        'sale' => 'Sold',
        'return' => 'Returned',
        'adjustment' => 'Adjustment',
        'cancellation' => 'Order cancelled',
        'expired' => 'Expired',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'product_id')->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ShopProductVariant::class, 'variant_id');
    }

    /** "Nitrile Gloves — Large · Box of 100" */
    public function itemName(): string
    {
        $name = $this->product?->name ?? '—';

        return $this->variant ? $name.' — '.$this->variant->title() : $name;
    }

    public function allocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ShopLotAllocation::class, 'movement_id');
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(ShopGrn::class, 'grn_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst($this->type);
    }
}
