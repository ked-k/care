<?php

namespace App\Livewire\Shop\Storefront;

use App\Models\Shop\ShopStore;
use App\Services\Shop\CartService;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The basket button in the storefront header, plus the slide-in basket that
 * opens whenever something is added (listens for 'cart-updated').
 * Embedded once in layouts/shop.blade.php.
 */
class CartDrawerComponent extends Component
{
    #[Locked]
    public string $storeId;

    public bool $open = false;

    public ?string $justAdded = null;

    public function mount(string $storeId): void
    {
        $this->storeId = $storeId;
    }

    private function cart(): CartService
    {
        return CartService::for(ShopStore::findOrFail($this->storeId));
    }

    #[On('cart-updated')]
    public function cartUpdated(?int $count = null, ?string $added = null): void
    {
        if ($added) {
            $this->justAdded = $added;
            $this->open = true;
        }
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
        $this->justAdded = null;
    }

    public function close(): void
    {
        $this->open = false;
        $this->justAdded = null;
    }

    public function updateQty(string $itemId, int $qty): void
    {
        if ($message = $this->cart()->setItemQty($itemId, $qty)) {
            $this->dispatch('toast', message: $message, type: 'warning');
        }
        // Keeps the full basket page in step if it's open underneath.
        $this->dispatch('cart-updated', count: $this->cart()->count());
    }

    public function render()
    {
        $cart = $this->cart();
        $store = $cart->store;

        return view('livewire.shop.storefront.cart-drawer', [
            'store' => $store,
            'count' => $cart->count(),
            'totals' => $this->open ? $cart->totals() : null,
        ]);
    }
}
