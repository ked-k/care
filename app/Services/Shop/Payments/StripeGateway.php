<?php

namespace App\Services\Shop\Payments;

use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopStore;
use App\Services\Shop\ShopMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Checkout (hosted payment page). No SDK — two plain HTTPS calls to
 * Stripe's REST API through Laravel's HTTP client.
 *
 * Which wallets appear (Apple Pay, Google Pay, Link, Klarna…) is controlled
 * in the Stripe dashboard's payment-method settings, not here: we don't pass
 * payment_method_types, so Stripe shows everything the account has enabled.
 *
 * Payment is confirmed two ways and either is enough (markPaid() is
 * idempotent): the customer's return to success_url, and the
 * checkout.session.completed webhook — the webhook still fires if the
 * customer closes the tab before the redirect.
 */
class StripeGateway implements PaymentGateway
{
    private const API = 'https://api.stripe.com/v1';

    public function code(): string
    {
        return 'stripe';
    }

    public function label(ShopStore $store): string
    {
        return __('Card, Apple Pay or Google Pay');
    }

    public function description(ShopStore $store): string
    {
        return __('Pay securely with Stripe. You’ll come straight back here afterwards.');
    }

    public function icon(): string
    {
        return 'fas fa-credit-card';
    }

    public function isOnline(): bool
    {
        return true;
    }

    public function isAvailable(ShopStore $store): bool
    {
        return (bool) $store->paymentSetting('stripe', 'enabled')
            && filled($store->paymentSetting('stripe', 'secret_key'));
    }

    public function settingsFields(): array
    {
        return [
            ['key' => 'publishable_key', 'label' => 'Publishable key', 'type' => 'text', 'hint' => 'Starts pk_live_ (or pk_test_ while testing).'],
            ['key' => 'secret_key', 'label' => 'Secret key', 'type' => 'password', 'hint' => 'Starts sk_live_ (or sk_test_). Stored encrypted.'],
            ['key' => 'webhook_secret', 'label' => 'Webhook signing secret', 'type' => 'password', 'hint' => 'Starts whsec_. Add the webhook URL shown below in Stripe → Developers → Webhooks, event checkout.session.completed.'],
        ];
    }

    public function start(ShopOrder $order): ?string
    {
        $store = $order->store;
        $response = Http::asForm()
            ->withToken($store->paymentSetting('stripe', 'secret_key'))
            ->timeout(20)
            ->post(self::API.'/checkout/sessions', [
                'mode' => 'payment',
                'client_reference_id' => $order->id,
                'customer_email' => $order->email,
                'success_url' => route('shop.payment.return', [$store->slug, 'stripe', $order->id]).'?t='.$order->access_token.'&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('shop.payment.cancel', [$store->slug, $order->id]).'?t='.$order->access_token,
                'metadata' => ['order_id' => $order->id, 'order_number' => $order->number],
                'payment_intent_data' => [
                    'description' => $store->name.' order '.$order->number,
                    'metadata' => ['order_id' => $order->id, 'order_number' => $order->number],
                ],
                // One line for the whole order total, so Stripe's figure can
                // never drift from ours by a rounding penny.
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($order->currency),
                        'unit_amount' => (int) round((float) $order->total * 100),
                        'product_data' => [
                            'name' => __(':store order :number', ['store' => $store->name, 'number' => $order->number]),
                            'description' => __(':n items', ['n' => $order->itemCount()]),
                        ],
                    ],
                ]],
            ]);

        if (! $response->successful() || ! $response->json('url')) {
            Log::error('Stripe checkout session failed', ['order' => $order->number, 'status' => $response->status(), 'body' => $response->json('error.message')]);
            throw new PaymentException(__('Card payments are unavailable right now. Please try again or choose another way to pay.'));
        }

        $order->payments()->create([
            'gateway' => 'stripe',
            'provider_ref' => $response->json('id'),
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'pending',
        ]);

        return $response->json('url');
    }

    public function confirmReturn(ShopOrder $order, Request $request): bool
    {
        $sessionId = (string) $request->query('session_id');
        if ($sessionId === '' || ! $order->payments()->where('gateway', 'stripe')->where('provider_ref', $sessionId)->exists()) {
            return false;
        }

        $session = Http::withToken($order->store->paymentSetting('stripe', 'secret_key'))
            ->timeout(20)->get(self::API.'/checkout/sessions/'.$sessionId);

        return $session->successful() && $this->settleFromSession($order, $session->json());
    }

    /** Shared by the return redirect and the webhook. */
    public function settleFromSession(ShopOrder $order, array $session): bool
    {
        if (($session['payment_status'] ?? null) !== 'paid') {
            return false;
        }

        $amount = ($session['amount_total'] ?? 0) / 100;
        if (abs($amount - (float) $order->total) > 0.01) {
            Log::warning('Stripe amount mismatch', ['order' => $order->number, 'stripe' => $amount, 'order_total' => $order->total]);
            $order->log('payment_warning', __('Stripe reported :amount, which doesn’t match the order total. Check before dispatching.', ['amount' => $order->store->money($amount)]));
        }

        if ($order->markPaid('stripe', $session['id'] ?? null, $amount, null, [
            'payment_intent' => $session['payment_intent'] ?? null,
        ])) {
            ShopMailer::orderConfirmed($order);
        }

        return true;
    }

    /**
     * Verify a webhook's Stripe-Signature header ("t=...,v1=...,v1=...") —
     * HMAC-SHA256 of "{t}.{raw body}" with the endpoint's signing secret,
     * rejected if more than 5 minutes old.
     */
    public static function verifySignature(string $payload, string $header, string $secret, int $tolerance = 300): bool
    {
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($k === 't') {
                $timestamp = (int) $v;
            } elseif ($k === 'v1') {
                $signatures[] = $v;
            }
        }
        if (! $timestamp || ! $signatures || abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        foreach ($signatures as $sig) {
            if (hash_equals($expected, (string) $sig)) {
                return true;
            }
        }

        return false;
    }
}
