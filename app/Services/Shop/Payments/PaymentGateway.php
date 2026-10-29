<?php

namespace App\Services\Shop\Payments;

use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopStore;
use Illuminate\Http\Request;

/**
 * Batch 40: one way a shop customer can pay. Online gateways (Stripe,
 * PayPal) hand back a URL to send the customer to; offline ones (bank
 * transfer, pay on delivery) return null and the order waits for staff to
 * record the payment.
 *
 * Adding a gateway = one class implementing this + one line in
 * PaymentManager::GATEWAYS. Its settings live in
 * shop_stores.payment_settings[code] and are edited from the Shop workspace's
 * Payments settings tab, driven by settingsFields().
 */
interface PaymentGateway
{
    public function code(): string;

    /** Shown to customers at checkout, e.g. "Card, Apple Pay or Google Pay". */
    public function label(ShopStore $store): string;

    public function description(ShopStore $store): string;

    /** Font Awesome / iconkit class for the checkout option. */
    public function icon(): string;

    public function isOnline(): bool;

    /** Enabled in settings AND has every credential it needs. */
    public function isAvailable(ShopStore $store): bool;

    /**
     * Start paying for an order. Returns the URL to redirect the customer to,
     * or null when there's nothing to do online.
     *
     * @throws PaymentException when the provider refuses
     */
    public function start(ShopOrder $order): ?string;

    /** Customer came back from the provider — confirm and record payment. */
    public function confirmReturn(ShopOrder $order, Request $request): bool;

    /**
     * Admin settings form definition:
     * [['key' => 'secret_key', 'label' => 'Secret key', 'type' => 'password', 'hint' => '...'], ...]
     */
    public function settingsFields(): array;
}
