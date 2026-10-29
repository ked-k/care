<?php

namespace App\Livewire\Shop\Storefront;

use App\Livewire\Shop\Storefront\Concerns\AddsToCart;
use App\Models\Shop\ShopCategory;
use App\Models\Shop\ShopProduct;

class HomeComponent extends StorefrontComponent
{
    use AddsToCart;

    public function mount(string $storeSlug): void
    {
        $this->resolveStore($storeSlug);
    }

    public function render()
    {
        $store = $this->store();

        $categories = ShopCategory::where('store_id', $store->id)->active()->whereNull('parent_id')
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('name')
            ->get()
            ->filter(fn ($c) => $c->products_count > 0);

        $featured = ShopProduct::where('store_id', $store->id)->active()
            ->withCardData()
            ->orderByDesc('is_featured')->orderByDesc('views')->orderBy('name')
            ->limit(8)->get();

        $productCount = ShopProduct::where('store_id', $store->id)->active()->count();
        $delivery = $store->deliveryMethods()->active()->get();
        $freeOver = $delivery->whereNotNull('free_over')->min('free_over');

        // "Shop by category" tabs: the top four categories, four products each.
        $tabs = $categories->take(4)->mapWithKeys(fn (ShopCategory $cat) => [$cat->slug => [
            'category' => $cat,
            'products' => ShopProduct::where('store_id', $store->id)->active()->where('category_id', $cat->id)
                ->withCardData()
                ->orderByDesc('is_featured')->orderByDesc('views')->orderBy('name')
                ->limit(4)->get(),
        ]]);

        $dealCount = ShopProduct::where('store_id', $store->id)->active()
            ->where(fn ($w) => $w->whereHas('tiers')->orWhereHas('variants.tiers'))->count();

        return $this->page('livewire.shop.storefront.home', compact('categories', 'featured', 'productCount', 'delivery', 'freeOver', 'tabs', 'dealCount'), $store->tagline);
    }
}
