<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopStore;
use App\Services\Shop\Payments\PaymentManager;
use App\Services\Shop\Payments\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Batch 40: the few storefront endpoints that are plain HTTP rather than
 * Livewire pages — payment provider redirects, the Stripe webhook, sign-out.
 */
class StorefrontController extends Controller
{
    /** /store — with exactly one live shop, go straight to it. */
    public function index()
    {
        $live = ShopStore::where('is_live', true)->get();
        abort_if($live->isEmpty(), 404);

        if ($live->count() === 1) {
            return redirect()->route('shop.home', $live->first()->slug);
        }

        return view('shop.directory', ['stores' => $live]);
    }

    public function paymentReturn(Request $request, string $storeSlug, string $gateway, string $orderId)
    {
        $order = $this->orderFor($storeSlug, $orderId, (string) $request->query('t'));
        $driver = PaymentManager::get($gateway);
        abort_unless($driver && $order->payment_method === $gateway, 404);

        $url = route('shop.order', [$storeSlug, $order->number]).'?t='.$order->access_token;

        try {
            $paid = $order->payment_status === 'paid' || $driver->confirmReturn($order, $request);
        } catch (\Throwable $e) {
            Log::error('Payment return failed', ['order' => $order->number, 'error' => $e->getMessage()]);
            $paid = false;
        }

        return $paid
            ? redirect($url)->with('success', __('Payment received — thank you.'))
            : redirect($url)->with('warning', __('We couldn’t confirm your payment yet. If you completed it, this page will update shortly — otherwise you can try again below.'));
    }

    public function paymentCancel(Request $request, string $storeSlug, string $orderId)
    {
        $order = $this->orderFor($storeSlug, $orderId, (string) $request->query('t'));
        if ($order->payment_status !== 'paid') {
            $order->payments()->where('status', 'pending')->update(['status' => 'cancelled']);
            $order->log('payment_cancelled', __('Customer left the payment page without paying.'));
        }

        return redirect(route('shop.order', [$storeSlug, $order->number]).'?t='.$order->access_token)
            ->with('warning', __('Payment cancelled. Your order is saved — you can pay below whenever you’re ready.'));
    }

    public function stripeWebhook(Request $request, string $storeId)
    {
        $store = ShopStore::find($storeId);
        $secret = $store?->paymentSetting('stripe', 'webhook_secret');
        $payload = $request->getContent();

        if (! $store || ! $secret || ! StripeGateway::verifySignature($payload, (string) $request->header('Stripe-Signature'), $secret)) {
            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);
        if (in_array($event['type'] ?? null, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $session = $event['data']['object'] ?? [];
            $order = ShopOrder::where('store_id', $store->id)->find($session['metadata']['order_id'] ?? $session['client_reference_id'] ?? null);
            if ($order) {
                app(StripeGateway::class)->settleFromSession($order, $session);
            }
        }

        return response('ok');
    }

    public function logout(Request $request, string $storeSlug)
    {
        Auth::guard('shop')->logout();
        // Only the shop guard — a staff member previewing the shop stays signed in to CareAffinity.
        $request->session()->regenerateToken();

        return redirect()->route('shop.home', $storeSlug)->with('success', __('You’re signed out.'));
    }

    private function orderFor(string $storeSlug, string $orderId, string $token): ShopOrder
    {
        $store = ShopStore::where('slug', $storeSlug)->firstOrFail();
        $order = ShopOrder::where('store_id', $store->id)->findOrFail($orderId);
        abort_unless(hash_equals($order->access_token, $token), 404);

        return $order;
    }
}
