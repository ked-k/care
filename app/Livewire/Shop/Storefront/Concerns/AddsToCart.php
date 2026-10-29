<?php

namespace App\Livewire\Shop\Storefront\Concerns;

use App\Models\Shop\ShopProduct;

/** "Add to basket" for any storefront page that lists products. */
trait AddsToCart
{
    public function addToCart(string $productId, int $qty = 1, ?string $variantId = null): void
    {
        $product = ShopProduct::where('store_id', $this->storeId)->find($productId);
        if (! $product) {
            $this->notify(__('This product is not available.'), 'error');

            return;
        }
        $variant = $variantId ? $product->variants()->find($variantId) : null;

        $cart = $this->cart();
        $message = $cart->add($product, max(1, $qty), $variant);
        $count = $cart->count();

        // Opens the basket drawer in the header (CartDrawerComponent).
        $this->dispatch('cart-updated', count: $count, added: $product->id);

        if ($message) {
            $this->notify($message, 'warning');
        }
    }
}
