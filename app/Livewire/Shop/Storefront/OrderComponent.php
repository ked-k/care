<?php

namespace App\Livewire\Shop\Storefront;

use App\Models\Shop\ShopOrder;
use App\Services\Shop\Payments\BankTransferGateway;
use App\Services\Shop\Payments\PaymentException;
use App\Services\Shop\Payments\PaymentManager;
use Livewire\Attributes\Locked;

/**
 * An order's page: the thank-you screen after checkout, and the page linked
 * from every order email. Open to the customer who owns it (signed in), or
 * to anyone holding the order's secret access token (?t=…) — so guests can
 * see their own order without an account.
 */
class OrderComponent extends StorefrontComponent
{
    #[Locked]
    public string $orderId = '';

    public function mount(string $storeSlug, string $orderNumber): void
    {
        $store = $this->resolveStore($storeSlug);
        $order = ShopOrder::where('store_id', $store->id)->where('number', $orderNumber)->first();
        abort_unless($order, 404);

        $token = (string) request()->query('t');
        $ownsIt = $this->customer() && $order->customer_id === $this->customer()->id;
        abort_unless($ownsIt || ($token !== '' && hash_equals($order->access_token, $token)), 404);

        $this->orderId = $order->id;
    }

    public function order(): ShopOrder
    {
        return ShopOrder::with(['items.product.images', 'store', 'events' => fn ($q) => $q->where('visible_to_customer', true)])
            ->findOrFail($this->orderId);
    }

    /** Try an online payment again after it was cancelled or failed. */
    public function payNow(?string $method = null)
    {
        $order = $this->order();
        if ($order->payment_status === 'paid' || in_array($order->status, ['cancelled', 'refunded'], true)) {
            return null;
        }

        $available = array_filter(PaymentManager::available($this->store()), fn ($g) => $g->isOnline());
        $method = $method && isset($available[$method]) ? $method : (isset($available[$order->payment_method]) ? $order->payment_method : array_key_first($available));
        if (! $method) {
            $this->notify(__('Online payment isn’t available right now. Please contact us to pay.'), 'warning');

            return null;
        }

        if ($order->payment_method !== $method) {
            $order->update(['payment_method' => $method]);
        }

        try {
            return redirect()->away(PaymentManager::get($method)->start($order->fresh()));
        } catch (PaymentException $e) {
            $this->notify($e->getMessage(), 'error');

            return null;
        }
    }

    public function render()
    {
        $order = $this->order();
        $store = $this->store();
        $onlineMethods = array_filter(PaymentManager::available($store), fn ($g) => $g->isOnline());

        return $this->page('livewire.shop.storefront.order', [
            'order' => $order,
            'bankDetails' => $order->payment_method === 'bank_transfer' ? BankTransferGateway::details($store) : [],
            'bankInstructions' => $store->paymentSetting('bank_transfer', 'instructions'),
            'canPayOnline' => $order->payment_status !== 'paid'
                && ! in_array($order->status, ['cancelled', 'refunded'], true)
                && ! empty($onlineMethods),
            'onlineMethods' => $onlineMethods,
            'justPlaced' => $order->created_at->gt(now()->subMinutes(30)),
        ], __('Order :n', ['n' => $order->number]));
    }
}
