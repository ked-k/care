<?php

namespace App\Models\Shop;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Batch 40: a placed order. See the migration's doc comment for the two
 * status axes (fulfilment `status` vs money `payment_status`).
 */
class ShopOrder extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_id', 'customer_id', 'number', 'access_token', 'email', 'phone',
        'billing_address', 'shipping_address', 'delivery_method_id', 'delivery_name', 'delivery_price',
        'subtotal', 'discount_total', 'vat_total', 'total', 'currency', 'prices_include_vat', 'discount_code',
        'status', 'payment_status', 'payment_method', 'carrier', 'tracking_number',
        'customer_notes', 'staff_notes', 'paid_at', 'shipped_at', 'completed_at', 'cancelled_at',
        'stock_released', 'ip_address',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'delivery_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'vat_total' => 'decimal:2',
        'total' => 'decimal:2',
        'prices_include_vat' => 'boolean',
        'stock_released' => 'boolean',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public const STATUS_LABELS = [
        'pending' => 'Awaiting payment',
        'processing' => 'Processing',
        'shipped' => 'Dispatched',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ];

    public const PAYMENT_STATUS_LABELS = [
        'unpaid' => 'Unpaid',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopStore::class, 'store_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ShopCustomer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ShopPayment::class, 'order_id')->latest();
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShopOrderEvent::class, 'order_id')->latest();
    }

    public function statusLabel(): string
    {
        // A collection order is "ready to collect", not "dispatched".
        if ($this->status === 'shipped' && $this->shipping_address === null) {
            return __('Ready to collect');
        }

        return __(self::STATUS_LABELS[$this->status] ?? ucfirst($this->status));
    }

    public function paymentStatusLabel(): string
    {
        return __(self::PAYMENT_STATUS_LABELS[$this->payment_status] ?? ucfirst($this->payment_status));
    }

    public function paymentMethodLabel(): string
    {
        return \App\Services\Shop\Payments\PaymentManager::label($this->payment_method);
    }

    public function isCollection(): bool
    {
        return $this->shipping_address === null;
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('qty');
    }

    public function log(string $type, string $message, bool $visibleToCustomer = false, ?int $userId = null): ShopOrderEvent
    {
        return $this->events()->create([
            'type' => $type,
            'message' => $message,
            'visible_to_customer' => $visibleToCustomer,
            'user_id' => $userId,
        ]);
    }

    /**
     * Mark the order paid — idempotent, so a Stripe webhook and the
     * customer's own return redirect can both call it safely.
     */
    public function markPaid(string $gateway, ?string $providerRef = null, ?float $amount = null, ?User $by = null, array $payload = []): bool
    {
        return DB::transaction(function () use ($gateway, $providerRef, $amount, $by, $payload) {
            $order = static::whereKey($this->id)->lockForUpdate()->first();
            if ($order->payment_status === 'paid') {
                return false;
            }

            $order->payments()->updateOrCreate(
                ['gateway' => $gateway, 'provider_ref' => $providerRef ?: 'manual-'.now()->timestamp],
                [
                    'amount' => $amount ?? $order->total,
                    'currency' => $order->currency,
                    'status' => 'succeeded',
                    'payload' => $payload ?: null,
                    'recorded_by' => $by?->id,
                ]
            );

            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'status' => $order->status === 'pending' ? 'processing' : $order->status,
            ]);
            $order->log('paid', $by
                ? __('Payment of :amount recorded by :name', ['amount' => $order->store->money($amount ?? $order->total), 'name' => $by->name])
                : __('Payment of :amount received via :method', ['amount' => $order->store->money($amount ?? $order->total), 'method' => $order->paymentMethodLabel()]),
                true, $by?->id);

            $this->refresh();

            return true;
        });
    }

    /** Put stock back for every line (cancellations, failed payments). Idempotent. */
    public function releaseStock(?int $userId = null): void
    {
        DB::transaction(function () use ($userId) {
            $order = static::whereKey($this->id)->lockForUpdate()->first();
            if ($order->stock_released) {
                return;
            }
            foreach ($order->items as $item) {
                $product = ShopProduct::withTrashed()->find($item->product_id);
                if (! $product || ! $product->track_stock) {
                    continue;
                }
                // A size that has since been deleted can't take stock back.
                // Back into the same batches the order took it from.
                $extra = ['restore_order_id' => $order->id, 'order_item_id' => $item->id];
                if ($item->variant_id) {
                    ShopProductVariant::find($item->variant_id)?->adjustStock($item->qty, 'cancellation', $order->number, null, $order->id, $userId, $extra);
                } else {
                    $product->adjustStock($item->qty, 'cancellation', $order->number, null, $order->id, $userId, $extra);
                }
            }
            $order->update(['stock_released' => true]);
        });
        $this->refresh();
    }
}
