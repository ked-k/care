<?php

namespace App\Livewire\Shop\Storefront;

use Livewire\Attributes\On;

class CartComponent extends StorefrontComponent
{
    public string $discountCode = '';

    public function mount(string $storeSlug): void
    {
        $this->resolveStore($storeSlug);
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        // re-render
    }

    public function updateQty(string $itemId, int $qty): void
    {
        if ($message = $this->cart()->setItemQty($itemId, $qty)) {
            $this->notify($message, 'warning');
        }
        $this->dispatch('cart-updated', count: $this->cart()->count());
    }

    public function remove(string $itemId): void
    {
        $this->cart()->removeItem($itemId);
        $this->dispatch('cart-updated', count: $this->cart()->count());
    }

    public function applyDiscount(): void
    {
        $error = $this->cart()->applyDiscount($this->discountCode, $this->customer()?->email);
        if ($error) {
            $this->addError('discountCode', $error);

            return;
        }
        $this->resetErrorBag('discountCode');
        $this->discountCode = '';
        $this->notify(__('Discount applied.'));
    }

    public function removeDiscount(): void
    {
        $this->cart()->removeDiscount();
    }

    public function render()
    {
        $totals = $this->cart()->totals();

        return $this->page('livewire.shop.storefront.cart', compact('totals'), __('Your basket'));
    }
}
