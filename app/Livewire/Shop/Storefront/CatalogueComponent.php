<?php

namespace App\Livewire\Shop\Storefront;

use App\Livewire\Shop\Storefront\Concerns\AddsToCart;
use App\Models\Shop\ShopCategory;
use App\Models\Shop\ShopProduct;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/** Product listing: /products (all, or ?q= search) and /category/{slug}. */
class CatalogueComponent extends StorefrontComponent
{
    use AddsToCart, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: 'relevance')]
    public string $sort = 'relevance';

    #[Url(as: 'stock', except: false)]
    public bool $inStockOnly = false;

    #[Url(as: 'deals', except: false)]
    public bool $bulkOnly = false;

    public function mount(string $storeSlug, ?string $categorySlug = null): void
    {
        $this->resolveStore($storeSlug);
        if ($categorySlug) {
            $this->category = $categorySlug;
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'category', 'sort', 'inStockOnly', 'bulkOnly'], true)) {
            $this->resetPage();
        }
    }

    public function setCategory(string $slug): void
    {
        $this->category = $this->category === $slug ? '' : $slug;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'category', 'inStockOnly', 'bulkOnly');
        $this->sort = 'relevance';
        $this->resetPage();
    }

    public function render()
    {
        $store = $this->store();

        $categories = ShopCategory::where('store_id', $store->id)->active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('sort_order')->orderBy('name')->get()
            ->filter(fn ($c) => $c->products_count > 0)->values();

        $current = $this->category ? $categories->firstWhere('slug', $this->category) : null;

        $query = ShopProduct::where('store_id', $store->id)->active()
            ->withCardData()
            ->search($this->search)
            ->when($current, fn ($q) => $q->where('category_id', $current->id))
            ->when($this->inStockOnly, fn ($q) => $q->where(fn ($w) => $w->where('track_stock', false)->orWhere('stock_qty', '>', 0)->orWhere('allow_backorder', true)))
            ->when($this->bulkOnly, fn ($q) => $q->where(fn ($w) => $w->whereHas('tiers', fn ($t) => $t->where('min_qty', '>', 1))
                ->orWhereHas('variants.tiers', fn ($t) => $t->where('min_qty', '>', 1))));

        match ($this->sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name' => $query->orderBy('name'),
            'newest' => $query->latest(),
            default => $query->orderByDesc('is_featured')->orderByDesc('views')->orderBy('name'),
        };

        $products = $query->paginate(24);

        $title = $current?->name ?? ($this->search !== '' ? __('Results for “:q”', ['q' => $this->search]) : __('All products'));

        return $this->page('livewire.shop.storefront.catalogue', compact('categories', 'current', 'products', 'title'), $title);
    }
}
