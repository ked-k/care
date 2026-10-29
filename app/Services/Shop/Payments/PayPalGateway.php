<?php

namespace App\Services\Shop\Payments;

use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopStore;
use App\Services\Shop\ShopMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayPal Checkout (Orders v2 API), redirect flow: create a PayPal order,
 * send the customer to PayPal to approve, capture it when they come back.
 * No SDK — Laravel's HTTP client only.
 */
class PayPalGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'paypal';
    }

    public function label(ShopStore $store): string
    {
        return 'PayPal';
    }

    public function description(ShopStore $store): string
    {
        return __('Pay with your PayPal account or PayPal Pay in 3.');
    }

    public function icon(): string
    {
        return 'fab fa-paypal';
    }

    public function isOnline(): bool
    {
        return true;
    }

    public function isAvailable(ShopStore $store): bool
    {
        return (bool) $store->paymentSetting('paypal', 'enabled')
            && filled($store->paymentSetting('paypal', 'client_id'))
            && filled($store->paymentSetting('paypal', 'client_secret'));
    }

    public function settingsFields(): array
    {
        return [
            ['key' => 'mode', 'label' => 'Mode', 'type' => 'select', 'options' => ['live' => 'Live', 'sandbox' => 'Sandbox (testing)']],
            ['key' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'hint' => 'PayPal Developer Dashboard → Apps & Credentials.'],
            ['key' => 'client_secret', 'label' => 'Secret', 'type' => 'password', 'hint' => 'Stored encrypted.'],
        ];
    }

    private function base(ShopStore $store): string
    {
        return $store->paymentSetting('paypal', 'mode', 'live') === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function token(ShopStore $store): string
    {
        $response = Http::asForm()
            ->withBasicAuth($store->paymentSetting('paypal', 'client_id'), $store->paymentSetting('paypal', 'client_secret'))
            ->timeout(20)
            ->post($this->base($store).'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if (! $response->successful()) {
            Log::error('PayPal auth failed', ['status' => $response->status()]);
            throw new PaymentException(__('PayPal is unavailable right now. Please try again or choose another way to pay.'));
        }

        return $response->json('access_token');
    }

    public function start(ShopOrder $order): ?string
    {
        $store = $order->store;
        $response = Http::withToken($this->token($store))
            ->withHeaders(['PayPal-Request-Id' => 'order-'.$order->id.'-'.now()->timestamp])
            ->timeout(20)
            ->post($this->base($store).'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->number,
                    'custom_id' => $order->id,
                    'description' => mb_substr($store->name.' order '.$order->number, 0, 127),
                    'amount' => [
                        'currency_code' => $order->currency,
                        'value' => number_format((float) $order->total, 2, '.', ''),
                    ],
                ]],
                'payment_source' => [
                    'paypal' => [
                        'experience_context' => [
                            'brand_name' => mb_substr($store->name, 0, 127),
                            'user_action' => 'PAY_NOW',
                            'shipping_preference' => 'NO_SHIPPING',
                            'return_url' => route('shop.payment.return', [$store->slug, 'paypal', $order->id]).'?t='.$order->access_token,
                            'cancel_url' => route('shop.payment.cancel', [$store->slug, $order->id]).'?t='.$order->access_token,
                        ],
                    ],
                ],
            ]);

        $approve = collect($response->json('links', []))->first(fn ($l) => in_array($l['rel'] ?? '', ['payer-action', 'approve'], true));

        if (! $response->successful() || ! $approve) {
            Log::error('PayPal create order failed', ['order' => $order->number, 'status' => $response->status(), 'body' => $response->json('message')]);
            throw new PaymentException(__('PayPal is unavailable right now. Please try again or choose another way to pay.'));
        }

        $order->payments()->create([
            'gateway' => 'paypal',
            'provider_ref' => $response->json('id'),
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'pending',
        ]);

        return $approve['href'];
    }

    public function confirmReturn(ShopOrder $order, Request $request): bool
    {
        // PayPal appends ?token={paypal order id}
        $paypalOrderId = (string) $request->query('token');
        $payment = $order->payments()->where('gateway', 'paypal')->where('provider_ref', $paypalOrderId)->first();
        if (! $payment) {
            return false;
        }
        if ($order->payment_status === 'paid') {
            return true;
        }

        $store = $order->store;
        $response = Http::withToken($this->token($store))
            ->withHeaders(['PayPal-Request-Id' => 'capture-'.$paypalOrderId])
            ->timeout(20)
            ->withBody('{}', 'application/json')
            ->post($this->base($store).'/v2/checkout/orders/'.$paypalOrderId.'/capture');

        if (! $response->successful() || $response->json('status') !== 'COMPLETED') {
            Log::warning('PayPal capture not completed', ['order' => $order->number, 'status' => $response->status(), 'paypal_status' => $response->json('status')]);
            $payment->update(['status' => 'failed', 'payload' => ['paypal_status' => $response->json('status')]]);

            return false;
        }

        $capture = $response->json('purchase_units.0.payments.captures.0', []);
        $amount = (float) ($capture['amount']['value'] ?? $order->total);

        if ($order->markPaid('paypal', $paypalOrderId, $amount, null, ['capture_id' => $capture['id'] ?? null])) {
            ShopMailer::orderConfirmed($order);
        }

        return true;
    }
}
