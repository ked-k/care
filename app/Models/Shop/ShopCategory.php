<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShopCategory extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_id', 'parent_id', 'name', 'slug', 'description', 'icon',
        'image_path', 'sort_order', 'is_featured', 'is_active',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopStore::class, 'store_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(ShopProduct::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * A sensible default icon for a medical-supplies category name. Only
     * icons present in the bundled Font Awesome 5.5 (public/plugins).
     */
    public static function guessIcon(string $name): string
    {
        $n = Str::lower($name);

        return match (true) {
            str_contains($n, 'glove') => 'fas fa-hand-paper',
            str_contains($n, 'hand') => 'fas fa-hands',
            str_contains($n, 'first aid'), str_contains($n, 'trauma'), str_contains($n, 'bleed') => 'fas fa-first-aid',
            str_contains($n, 'wipe'), str_contains($n, 'paper') => 'fas fa-toilet-paper',
            str_contains($n, 'protective'), str_contains($n, 'ppe') => 'fas fa-user-shield',
            str_contains($n, 'diagnostic') => 'fas fa-stethoscope',
            str_contains($n, 'mobility'), str_contains($n, 'living') => 'fas fa-wheelchair',
            str_contains($n, 'syringe'), str_contains($n, 'needle') => 'fas fa-syringe',
            str_contains($n, 'resus') => 'fas fa-heartbeat',
            str_contains($n, 'pharma') => 'fas fa-pills',
            str_contains($n, 'sign') => 'fas fa-sign',
            str_contains($n, 'furniture'), str_contains($n, 'equipment') => 'fas fa-procedures',
            str_contains($n, 'support'), str_contains($n, 'brace') => 'fas fa-walking',
            str_contains($n, 'rehab'), str_contains($n, 'fitness') => 'fas fa-dumbbell',
            str_contains($n, 'personal care') => 'fas fa-bath',
            str_contains($n, 'hygiene'), str_contains($n, 'infection') => 'fas fa-spray-can',
            str_contains($n, 'operation') => 'fas fa-cut',
            str_contains($n, 'facilit') => 'fas fa-broom',
            str_contains($n, 'clearance') => 'fas fa-tags',
            default => 'fas fa-box-open',
        };
    }
}
