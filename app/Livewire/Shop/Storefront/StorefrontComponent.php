<?php

namespace App\Livewire\Shop\Storefront;

use App\Http\Middleware\ResolveShopStore;
use App\Models\Shop\ShopCustomer;
use App\Models\Shop\ShopStore;
use App\Services\Shop\CartService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Batch 40: shared plumbing for every public storefront page.
 *
 * The store is resolved once in mount (from the {storeSlug} route segment)
 * and pinned as a #[Locked] id, because Livewire's own update requests don't
 * re-run the 'shop.store' route middleware.
 */
abstract class StorefrontComponent extends Component
{
    #[Locked]
    public string $storeId = '';

    private ?ShopStore $resolvedStore = null;

    protected function resolveStore(string $storeSlug): ShopStore
    {
        $store = ShopStore::where('slug', $storeSlug)->first();
        abort_unless($store && ResolveShopStore::canView($store), 404);
        $this->storeId = $store->id;
        $this->resolvedStore = $store;

        return $store;
    }

    public function store(): ShopStore
    {
        return $this->resolvedStore ??= ShopStore::findOrFail($this->storeId);
    }

    protected function cart(): CartService
    {
        return CartService::for($this->store());
    }

    protected function customer(): ?ShopCustomer
    {
        $customer = Auth::guard('shop')->user();

        return $customer && $customer->store_id === $this->storeId ? $customer : null;
    }

    protected function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }

    /** Render a storefront view inside layouts/shop.blade.php. */
    protected function page(string $view, array $data = [], ?string $title = null)
    {
        $store = $this->store();

        return view($view, $data + ['store' => $store])
            ->layout('layouts.shop', ['store' => $store, 'pageTitle' => $title]);
    }
}
