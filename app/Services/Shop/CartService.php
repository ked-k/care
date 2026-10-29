<?php

namespace App\Services\Shop;

use App\Models\Shop\ShopCart;
use App\Models\Shop\ShopCustomer;
use App\Models\Shop\ShopDeliveryMethod;
use App\Models\Shop\ShopDiscount;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductVariant;
use App\Models\Shop\ShopStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Batch 40: the current visitor's basket for one store.
 *
 * A guest's cart is found by a token kept in the session; a signed-in
 * customer's by customer_id, so it follows them between devices. Signing in
 * merges any guest basket into the customer's own (see mergeGuestCart()).
 *
 * All money maths lives in totals() so the basket page, the header badge and
 * checkout can never disagree.
 *
 * Batch 41: a basket line is a product, or a product + variant ("Nitrile
 * Gloves — Large"). Variants that share the product's bulk prices count
 * together towards them: 6 Small + 6 Large reaches the "10+" price.
 */
class CartService
{
    private ?ShopCart $cart = null;

    public function __construct(public ShopStore $store)
    {
    }

    public static function for(ShopStore $store): self
    {
        return new self($store);
    }

    private function sessionKey(): string
    {
        return 'shop_cart.'.$this->store->id;
    }

    public function customer(): ?ShopCustomer
    {
        $customer = Auth::guard('shop')->user();

        return $customer && $customer->store_id === $this->store->id ? $customer : null;
    }

    /** The cart if one exists already — never creates one (for read-only views). */
    public function existing(): ?ShopCart
    {
        if ($this->cart) {
            return $this->cart;
        }

        if ($customer = $this->customer()) {
            $this->cart = ShopCart::where('store_id', $this->store->id)->where('customer_id', $customer->id)->latest()->first();
        }

        if (! $this->cart && ($token = session($this->sessionKey()))) {
            $this->cart = ShopCart::where('store_id', $this->store->id)->where('token', $token)->first();
        }

        return $this->cart;
    }

    public function cart(): ShopCart
    {
        if ($cart = $this->existing()) {
            return $cart;
        }

        $token = Str::random(48);
        $this->cart = ShopCart::create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer()?->id,
            'token' => $token,
        ]);
        session([$this->sessionKey() => $token]);

        return $this->cart;
    }

    /** After a customer signs in: fold the guest basket into theirs. */
    public function mergeGuestCart(ShopCustomer $customer): void
    {
        $token = session($this->sessionKey());
        $guest = $token ? ShopCart::where('store_id', $this->store->id)->where('token', $token)->whereNull('customer_id')->first() : null;
        $own = ShopCart::where('store_id', $this->store->id)->where('customer_id', $customer->id)->latest()->first();

        if (! $guest) {
            return;
        }
        if (! $own) {
            $guest->update(['customer_id' => $customer->id]);

            return;
        }
        foreach ($guest->items as $item) {
            $existing = $this->findItem($own, $item->product_id, $item->variant_id);
            $existing
                ? $existing->update(['qty' => $existing->qty + $item->qty])
                : $own->items()->create(['product_id' => $item->product_id, 'variant_id' => $item->variant_id, 'qty' => $item->qty]);
        }
        $guest->delete();
        session([$this->sessionKey() => $own->token]);
        $this->cart = $own;
    }

    private function findItem(ShopCart $cart, string $productId, ?string $variantId)
    {
        return $cart->items()->where('product_id', $productId)
            ->when($variantId, fn ($q) => $q->where('variant_id', $variantId), fn ($q) => $q->whereNull('variant_id'))
            ->first();
    }

    /** Checks a variant belongs to the product and can be sold. Null when fine. */
    private function variantProblem(ShopProduct $product, ?ShopProductVariant $variant): ?string
    {
        if (! $product->has_variants) {
            return $variant ? __('This product is not available.') : null;
        }
        if (! $variant) {
            return __('Choose a size or option for :name first.', ['name' => $product->name]);
        }
        if ($variant->product_id !== $product->id || ! $variant->is_active) {
            return __('That option is no longer available.');
        }
        $variant->setRelation('product', $product);

        return null;
    }

    /** "Nitrile Gloves — Large · Box of 100" */
    public static function lineName(ShopProduct $product, ?ShopProductVariant $variant): string
    {
        return $variant ? $product->name.' — '.$variant->title() : $product->name;
    }

    /** @return string|null an error message for the customer, or null on success */
    public function add(ShopProduct $product, int $qty = 1, ?ShopProductVariant $variant = null): ?string
    {
        if ($product->store_id !== $this->store->id || $product->status !== 'active') {
            return __('This product is not available.');
        }
        if ($problem = $this->variantProblem($product, $variant)) {
            return $problem;
        }
        if (! ($variant ? $variant->inStock() : $product->inStock())) {
            return __(':name is out of stock.', ['name' => self::lineName($product, $variant)]);
        }

        $item = $this->findItem($this->cart(), $product->id, $variant?->id);
        $newQty = ($item?->qty ?? 0) + max(1, $qty);

        return $this->setQty($product, $newQty, $variant);
    }

    /** @return string|null an error/notice message, or null on success */
    public function setQty(ShopProduct $product, int $qty, ?ShopProductVariant $variant = null): ?string
    {
        $cart = $this->cart();

        if ($qty <= 0) {
            $this->findItem($cart, $product->id, $variant?->id)?->delete();
            $cart->touch();

            return null;
        }
        if ($problem = $this->variantProblem($product, $variant)) {
            return $problem;
        }

        $name = self::lineName($product, $variant);
        $notice = null;
        $min = max(1, (int) $product->min_order_qty);
        $max = $variant ? $variant->maxPurchasableQty() : $product->maxPurchasableQty();

        if ($qty < $min) {
            $qty = $min;
            $notice = __(':name has a minimum order of :n.', ['name' => $name, 'n' => $min]);
        }
        if ($max <= 0) {
            return __(':name is out of stock.', ['name' => $name]);
        }
        if ($qty > $max) {
            $qty = $max;
            $notice = __('Only :n of :name available — your basket has been updated.', ['n' => $max, 'name' => $name]);
        }

        $item = $this->findItem($cart, $product->id, $variant?->id);
        $item
            ? $item->update(['qty' => $qty])
            : $cart->items()->create(['product_id' => $product->id, 'variant_id' => $variant?->id, 'qty' => $qty]);
        $cart->touch();

        return $notice;
    }

    /** Change one basket line by its id (basket page and drawer). */
    public function setItemQty(string $itemId, int $qty): ?string
    {
        $item = $this->existing()?->items()->with(['product', 'variant'])->find($itemId);
        if (! $item || ! $item->product) {
            return null;
        }

        return $this->setQty($item->product, $qty, $item->variant);
    }

    public function removeItem(string $itemId): void
    {
        $this->existing()?->items()->whereKey($itemId)->delete();
    }

    public function clear(): void
    {
        if ($cart = $this->existing()) {
            $cart->items()->delete();
            $cart->update(['discount_code' => null]);
        }
    }

    public function count(): int
    {
        return (int) ($this->existing()?->items()->sum('qty') ?? 0);
    }

    /** @return string|null an error message, or null when the code was applied */
    public function applyDiscount(string $code, ?string $email = null): ?string
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return __('Enter a discount code.');
        }

        $discount = ShopDiscount::findByCode($this->store->id, $code);
        if (! $discount) {
            return __('That code isn’t recognised.');
        }

        $subtotal = $this->totals()['subtotal'];
        if ($reason = $discount->rejectionReason($subtotal, $email)) {
            return $reason;
        }

        $this->cart()->update(['discount_code' => $discount->code]);

        return null;
    }

    public function removeDiscount(): void
    {
        $this->existing()?->update(['discount_code' => null]);
    }

    public function setDeliveryMethod(?string $methodId): void
    {
        $this->cart()->update(['delivery_method_id' => $methodId]);
    }

    /**
     * Everything the basket and checkout need, in one place.
     *
     * @return array{
     *   lines: array<int, array{item_id: string, product: ShopProduct, variant: ?ShopProductVariant, name: string, title: ?string, sku: ?string, image_url: ?string, qty: int, unit_price: float, line_total: float, vat_rate: float, saving: float, mixed: bool}>,
     *   count: int, subtotal: float, discount: float, discount_code: ?string, discount_label: ?string, discount_error: ?string,
     *   delivery_method: ?ShopDeliveryMethod, delivery: float, free_delivery_gap: ?float,
     *   vat: float, total: float, prices_include_vat: bool, problems: array<int, string>
     * }
     */
    public function totals(?string $deliveryMethodId = null, ?string $email = null): array
    {
        $cart = $this->existing();
        $items = $cart ? $cart->items()->with(['product.tiers', 'product.images', 'variant.tiers', 'variant.image'])->oldest()->get() : collect();

        // Keep only lines that can still be bought.
        $valid = [];
        $problems = [];
        foreach ($items as $item) {
            $product = $item->product;
            $variant = $item->variant;
            $variant?->setRelation('product', $product);
            if (! $product || $product->status !== 'active'
                || ($product->has_variants && (! $variant || ! $variant->is_active))
                || (! $product->has_variants && $item->variant_id)) {
                $problems[] = __('An item in your basket is no longer available and has been left out.');
                continue;
            }
            $valid[] = $item;
        }

        // Variants on the product's shared bulk prices count together ("mix sizes").
        $sharedQty = [];
        foreach ($valid as $item) {
            if ($item->variant && $item->variant->usesProductLadder()) {
                $sharedQty[$item->product_id] = ($sharedQty[$item->product_id] ?? 0) + $item->qty;
            }
        }

        $lines = [];
        foreach ($valid as $item) {
            $product = $item->product;
            $variant = $item->variant;
            $shared = $variant && $variant->usesProductLadder();
            $tierQty = $shared ? $sharedQty[$product->id] : (int) $item->qty;
            $unit = $variant ? $variant->unitPriceFor($tierQty) : $product->unitPriceFor($tierQty);
            $base = $variant ? $variant->basePrice() : (float) $product->price;

            $lines[] = [
                'item_id' => $item->id,
                'product' => $product,
                'variant' => $variant,
                'name' => self::lineName($product, $variant),
                'title' => $variant?->title(),
                'sku' => $variant?->sku ?: $product->sku,
                'image_url' => $variant?->image?->url() ?? $product->primaryImageUrl(),
                'qty' => (int) $item->qty,
                'unit_price' => $unit,
                'line_total' => round($unit * $item->qty, 2),
                'vat_rate' => (float) $product->vat_rate,
                'saving' => round(($base - $unit) * $item->qty, 2),
                'mixed' => $shared && $tierQty > $item->qty,
            ];

            $stock = $variant ? $variant->stock_qty : $product->stock_qty;
            if ($product->track_stock && ! $product->allow_backorder && $item->qty > $stock) {
                $problems[] = __('Only :n of :name left in stock.', ['n' => max(0, $stock), 'name' => self::lineName($product, $variant)]);
            }
        }

        $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);

        // Discount
        $discount = 0.0;
        $discountLabel = null;
        $discountError = null;
        $freeDelivery = false;
        $code = $cart?->discount_code;
        if ($code) {
            $d = ShopDiscount::findByCode($this->store->id, $code);
            $reason = $d ? $d->rejectionReason($subtotal, $email) : __('That code isn’t recognised.');
            if ($reason) {
                $discountError = $reason;
                $code = null;
            } else {
                $discount = $d->amountOff($subtotal);
                $freeDelivery = $d->type === 'free_delivery';
                $discountLabel = $d->summary();
            }
        }

        // Delivery
        $methodId = $deliveryMethodId ?? $cart?->delivery_method_id;
        $methods = $this->store->deliveryMethods()->active()->get();
        $method = $methods->firstWhere('id', $methodId) ?? $methods->first();
        $delivery = $method ? $method->priceFor($subtotal) : 0.0;
        if ($freeDelivery) {
            $delivery = 0.0;
        }
        $freeGap = null;
        if ($method && $method->free_over !== null && $delivery > 0) {
            $freeGap = round((float) $method->free_over - $subtotal, 2);
        }

        // VAT — worked out per line after spreading the discount pro rata,
        // plus delivery at the store's default rate (UK delivery follows the
        // goods' VAT treatment; the default rate is the common case).
        $ratio = $subtotal > 0 ? max(0, ($subtotal - $discount) / $subtotal) : 0;
        $inclusive = $this->store->prices_include_vat;
        $vat = 0.0;
        foreach ($lines as $line) {
            $vat += Vat::of($line['line_total'] * $ratio, $line['vat_rate'], $inclusive);
        }
        $vat += Vat::of($delivery, (float) $this->store->default_vat_rate, $inclusive);
        $vat = round($vat, 2);

        $total = round($subtotal - $discount + $delivery + ($inclusive ? 0 : $vat), 2);

        return [
            'lines' => $lines,
            'count' => (int) array_sum(array_column($lines, 'qty')),
            'subtotal' => $subtotal,
            'discount' => round($discount, 2),
            'discount_code' => $code,
            'discount_label' => $discountLabel,
            'discount_error' => $discountError,
            'delivery_method' => $method,
            'delivery' => round($delivery, 2),
            'free_delivery_gap' => $freeGap,
            'vat' => $vat,
            'total' => max(0, $total),
            'prices_include_vat' => $inclusive,
            'problems' => array_values(array_unique($problems)),
        ];
    }
}
