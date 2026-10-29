<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ShopProductImage extends Model
{
    use HasUuids;

    protected $fillable = ['product_id', 'path', 'alt', 'sort_order'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'product_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
