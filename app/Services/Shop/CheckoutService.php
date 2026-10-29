<?php

namespace App\Services\Shop;

use App\Models\Shop\ShopCustomer;
use App\Models\Shop\ShopDiscount;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductVariant;
use App\Models\Shop\ShopStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Batch 40: turns a basket into an order.
 *
 * Stock is taken at the moment the order is placed (so two people can't buy
 * the last box), and given back if the order is cancelled or an online
 * payment is abandoned (see ShopOrder::releaseStock() and the
 * shop:release-unpaid command).
 */
class CheckoutService
{
    /**
     * @param array{
     *   email: string, phone: ?string, billing: array, shipping: ?array,
     *   delivery_method_id: ?string, payment_method: string, notes: ?string,
     *   create_account: bool, password: ?string, marketing_opt_in: bool
     * } $data
     *
     * @throws RuntimeException with a customer-facing message
     */
    public function place(ShopStore $store, CartService $cart, array $data, ?string $ip = null): ShopOrder
    {
        return DB::transaction(function () use ($store, $cart, $data, $ip) {
            $totals = $cart->totals($data['delivery_method_id'] ?? null, $data['email']);

            if (empty($totals['lines'])) {
                throw new RuntimeException(__('Your basket is empty.'));
            }

            // Expired batches come off first, so they can never be sold.
            foreach ($totals['lines'] as $line) {
                if ($line['product']->track_stock && $line['product']->track_expiry) {
                    StockLedger::writeOffExpired($line['product'], $line['variant']);
                }
            }

            // Re-check stock under a lock so two shoppers can't both take the last box.
            foreach ($totals['lines'] as $line) {
                $product = ShopProduct::whereKey($line['product']->id)->lockForUpdate()->first();
                if (! $product || $product->status !== 'active') {
                    throw new RuntimeException(__(':name is no longer available. Please remove it from your basket.', ['name' => $line['name']]));
                }
                $stock = $product->stock_qty;
                if ($line['variant']) {
                    $variant = ShopProductVariant::whereKey($line['variant']->id)->lockForUpdate()->first();
                    if (! $variant || ! $variant->is_active) {
                        throw new RuntimeException(__(':name is no longer available. Please remove it from your basket.', ['name' => $line['name']]));
                    }
                    $stock = $variant->stock_qty;
                }
                if ($product->track_stock && ! $product->allow_backorder && $stock < $line['qty']) {
                    throw new RuntimeException($stock > 0
                        ? __('Only :n of :name left. Please update your basket.', ['n' => $stock, 'name' => $line['name']])
                        : __(':name has just sold out. Please remove it from your basket.', ['name' => $line['name']]));
                }
            }

            $method = $totals['delivery_method'];
            $isCollection = $method?->is_collection ?? false;

            $customer = $this->resolveCustomer($store, $data);

            $order = ShopOrder::create([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'number' => $store->reserveOrderNumber(),
                'access_token' => Str::random(48),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'billing_address' => $data['billing'],
                'shipping_address' => $isCollection ? null : ($data['shipping'] ?? $data['billing']),
                'delivery_method_id' => $method?->id,
                'delivery_name' => $method?->name,
                'delivery_price' => $totals['delivery'],
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount'],
                'vat_total' => $totals['vat'],
                'total' => $totals['total'],
                'currency' => $store->currency,
                'prices_include_vat' => $store->prices_include_vat,
                'discount_code' => $totals['discount_code'],
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $data['payment_method'],
                'customer_notes' => $data['notes'] ?? null,
                'ip_address' => $ip,
            ]);

            $ratio = $totals['subtotal'] > 0 ? ($totals['subtotal'] - $totals['discount']) / $totals['subtotal'] : 0;
            foreach ($totals['lines'] as $line) {
                $product = $line['product'];
                $variant = $line['variant'];
                $orderItem = $order->items()->create([
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'sku' => $line['sku'],
                    'name' => $line['name'],
                    'variant_title' => $variant?->describe(),
                    'pack_size' => $variant ? $this->variantPack($product, $variant) : $product->pack_size,
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'vat_rate' => $line['vat_rate'],
                    'vat_amount' => round(Vat::of($line['line_total'] * $ratio, $line['vat_rate'], $store->prices_include_vat), 2),
                    'line_total' => $line['line_total'],
                ]);

                if ($product->track_stock) {
                    // Taken from the batches in FEFO/FIFO order; the allocation records which.
                    ($variant ?? $product)->adjustStock(-$line['qty'], 'sale', $order->number, null, $order->id, null, ['order_item_id' => $orderItem->id]);
                }
            }

            if ($totals['discount_code']) {
                ShopDiscount::where('store_id', $store->id)->where('code', $totals['discount_code'])->increment('used_count');
            }

            $order->log('placed', __('Order placed — :method', ['method' => $order->paymentMethodLabel()]), true);

            $cart->clear();

            return $order->load('items', 'store');
        });
    }

    /** A "Pack" option's value is the line's pack size; otherwise the product's. */
    private function variantPack(ShopProduct $product, ShopProductVariant $variant): ?string
    {
        foreach ($product->optionNames() as $i => $name) {
            if (preg_match('/^pack/i', $name)) {
                return $variant->values()[$i] ?? $product->pack_size;
            }
        }

        return $product->pack_size;
    }

    private function resolveCustomer(ShopStore $store, array $data): ShopCustomer
    {
        $signedIn = Auth::guard('shop')->user();
        if ($signedIn && $signedIn->store_id === $store->id) {
            $this->rememberAddress($signedIn, $data['billing']);

            return $signedIn;
        }

        $customer = ShopCustomer::firstOrNew(['store_id' => $store->id, 'email' => $data['email']]);
        $customer->fill([
            'first_name' => $data['billing']['first_name'] ?? $customer->first_name ?? '',
            'last_name' => $data['billing']['last_name'] ?? $customer->last_name,
            'phone' => $data['phone'] ?? $customer->phone,
            'company' => $data['billing']['company'] ?? $customer->company,
            'marketing_opt_in' => $customer->marketing_opt_in || ! empty($data['marketing_opt_in']),
        ]);

        // Only set a password on a brand-new or guest-only record — never
        // let a guest checkout overwrite an existing account's password.
        if (! empty($data['create_account']) && ! $customer->hasAccount() && filled($data['password'] ?? null)) {
            $customer->password = $data['password'];
        }
        $customer->save();

        // Sign them in only when this checkout is what gave them a password —
        // a new account, or a guest-only record upgraded to an account.
        $passwordJustSet = $customer->wasChanged('password') || ($customer->wasRecentlyCreated && $customer->hasAccount());
        if (! empty($data['create_account']) && $passwordJustSet) {
            Auth::guard('shop')->login($customer);
            $this->rememberAddress($customer, $data['billing']);
        }

        return $customer;
    }

    private function rememberAddress(ShopCustomer $customer, array $billing): void
    {
        if (empty($billing['line1']) || $customer->addresses()->where('line1', $billing['line1'])->where('postcode', $billing['postcode'] ?? '')->exists()) {
            return;
        }
        $customer->addresses()->create([
            'name' => trim(($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '')),
            'company' => $billing['company'] ?? null,
            'line1' => $billing['line1'],
            'line2' => $billing['line2'] ?? null,
            'city' => $billing['city'] ?? '',
            'county' => $billing['county'] ?? null,
            'postcode' => $billing['postcode'] ?? '',
            'country' => $billing['country'] ?? 'GB',
            'is_default' => ! $customer->addresses()->exists(),
        ]);
    }
}
