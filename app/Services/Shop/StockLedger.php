<?php

namespace App\Services\Shop;

use App\Models\Shop\ShopLotAllocation;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductVariant;
use App\Models\Shop\ShopStockLot;
use App\Models\Shop\ShopStockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Batch 42: the one place stock changes. Every change:
 *   1. moves stock_qty on the product (or variant — the product then holds
 *      the total of its variants),
 *   2. writes a ledger row (shop_stock_movements),
 *   3. keeps the batches (shop_stock_lots) in step:
 *        stock in  → a new lot (with lot number / expiry / cost when given),
 *                    or, for a cancelled order, back into the lots it came from;
 *        stock out → taken from the lots in rotation order:
 *                    FEFO = soonest expiry first (no-expiry lots last), then oldest;
 *                    FIFO = oldest received first.
 *      Each lot touched gets an allocation row, so any lot can be traced to
 *      the orders it went out on.
 *
 * ShopProduct::adjustStock() and ShopProductVariant::adjustStock() call this.
 */
class StockLedger
{
    /**
     * @param array{
     *   reference?: ?string, note?: ?string, order_id?: ?string, order_item_id?: ?string, user_id?: ?int, grn_id?: ?string,
     *   lot?: array{lot_number?: ?string, expiry_date?: ?string, unit_cost?: ?float, grn_line_id?: ?string, source?: string, received_on?: ?string},
     *   from_lot_id?: ?string, only_lot?: bool, restore_order_id?: ?string
     * } $opts
     */
    public static function record(ShopProduct $product, ?ShopProductVariant $variant, int $qty, string $type, array $opts = []): ShopStockMovement
    {
        return DB::transaction(function () use ($product, $variant, $qty, $type, $opts) {
            if ($variant) {
                $row = ShopProductVariant::whereKey($variant->id)->lockForUpdate()->first();
                $row->stock_qty += $qty;
                $row->save();
                $variant->stock_qty = $row->stock_qty;
                $balance = $row->stock_qty;
                ShopProduct::withTrashed()->whereKey($product->id)->update([
                    'stock_qty' => (int) ShopProductVariant::where('product_id', $product->id)->sum('stock_qty'),
                ]);
            } else {
                $row = ShopProduct::withTrashed()->whereKey($product->id)->lockForUpdate()->first();
                $row->stock_qty += $qty;
                $row->saveQuietly();
                $product->stock_qty = $row->stock_qty;
                $balance = $row->stock_qty;
            }

            $movement = ShopStockMovement::create([
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'qty' => $qty,
                'type' => $type,
                'reference' => $opts['reference'] ?? null,
                'note' => $opts['note'] ?? null,
                'order_id' => $opts['order_id'] ?? null,
                'grn_id' => $opts['grn_id'] ?? null,
                'balance_after' => $balance,
                'user_id' => $opts['user_id'] ?? null,
            ]);

            if ($qty > 0) {
                self::stockIn($product, $variant, $qty, $type, $movement, $opts);
            } elseif ($qty < 0) {
                self::stockOut($product, $variant, -$qty, $movement, $opts);
            }

            return $movement;
        });
    }

    private static function lotsQuery(ShopProduct $product, ?ShopProductVariant $variant)
    {
        return ShopStockLot::where('product_id', $product->id)
            ->when($variant, fn ($q) => $q->where('variant_id', $variant->id), fn ($q) => $q->whereNull('variant_id'));
    }

    private static function stockIn(ShopProduct $product, ?ShopProductVariant $variant, int $qty, string $type, ShopStockMovement $movement, array $opts): void
    {
        // A cancelled order: back into the very lots it was taken from.
        if (! empty($opts['restore_order_id'])) {
            $taken = ShopLotAllocation::where('order_id', $opts['restore_order_id'])
                ->whereHas('lot', fn ($q) => $q->where('product_id', $product->id)
                    ->when($variant, fn ($l) => $l->where('variant_id', $variant->id), fn ($l) => $l->whereNull('variant_id')))
                ->selectRaw('lot_id, SUM(qty) as net')->groupBy('lot_id')->havingRaw('SUM(qty) > 0')
                ->get();
            foreach ($taken as $row) {
                if ($qty <= 0) {
                    break;
                }
                $back = min($qty, (int) $row->net);
                $lot = ShopStockLot::whereKey($row->lot_id)->lockForUpdate()->first();
                if (! $lot) {
                    continue;
                }
                $lot->increment('qty_remaining', $back);
                ShopLotAllocation::create([
                    'lot_id' => $lot->id, 'movement_id' => $movement->id, 'qty' => -$back,
                    'order_id' => $opts['restore_order_id'], 'order_item_id' => $opts['order_item_id'] ?? null,
                ]);
                $qty -= $back;
            }
            if ($qty <= 0) {
                return;
            }
        }

        $lot = $opts['lot'] ?? [];
        $source = $lot['source'] ?? match ($type) {
            'receipt' => ! empty($opts['grn_id']) ? 'grn' : 'adjustment',
            'return', 'cancellation' => 'return',
            default => 'adjustment',
        };

        // Same lot number + expiry already on the shelf (and not a delivery
        // of its own): add to it rather than starting a second row.
        $existing = null;
        if (empty($opts['grn_id']) && ! empty($lot['lot_number'])) {
            $existing = self::lotsQuery($product, $variant)->where('lot_number', $lot['lot_number'])
                ->where(fn ($q) => ! empty($lot['expiry_date']) ? $q->whereDate('expiry_date', $lot['expiry_date']) : $q->whereNull('expiry_date'))
                ->lockForUpdate()->first();
        }

        if ($existing) {
            $existing->increment('qty_remaining', $qty);
            $existing->increment('qty_received', $qty);
            $lotId = $existing->id;
        } else {
            $lotId = ShopStockLot::create([
                'store_id' => $product->store_id,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'lot_number' => ($lot['lot_number'] ?? null) ?: null,
                'expiry_date' => ($lot['expiry_date'] ?? null) ?: null,
                'received_on' => ($lot['received_on'] ?? null) ?: today()->toDateString(),
                'qty_received' => $qty,
                'qty_remaining' => $qty,
                'unit_cost' => $lot['unit_cost'] ?? ($variant ? $variant->costPrice() : $product->cost_price),
                'source' => $source,
                'grn_id' => $opts['grn_id'] ?? null,
                'grn_line_id' => $lot['grn_line_id'] ?? null,
            ])->id;
        }

        ShopLotAllocation::create(['lot_id' => $lotId, 'movement_id' => $movement->id, 'qty' => -$qty, 'order_id' => $opts['order_id'] ?? null, 'order_item_id' => $opts['order_item_id'] ?? null]);
    }

    private static function stockOut(ShopProduct $product, ?ShopProductVariant $variant, int $need, ShopStockMovement $movement, array $opts): void
    {
        $lots = collect();
        if (! empty($opts['from_lot_id'])) {
            $lots = self::lotsQuery($product, $variant)->whereKey($opts['from_lot_id'])->where('qty_remaining', '>', 0)->lockForUpdate()->get();
        }
        if (empty($opts['only_lot'])) {
            $lots = $lots->concat(self::inRotationOrder(self::lotsQuery($product, $variant), $product)
                ->where('qty_remaining', '>', 0)
                ->when(! empty($opts['from_lot_id']), fn ($q) => $q->whereKeyNot($opts['from_lot_id']))
                ->lockForUpdate()->get());
        }

        foreach ($lots as $lot) {
            if ($need <= 0) {
                break;
            }
            $take = min($need, $lot->qty_remaining);
            $lot->decrement('qty_remaining', $take);
            ShopLotAllocation::create([
                'lot_id' => $lot->id, 'movement_id' => $movement->id, 'qty' => $take,
                'order_id' => $opts['order_id'] ?? null, 'order_item_id' => $opts['order_item_id'] ?? null,
            ]);
            $need -= $take;
        }
        // Anything left is a back order (negative stock) — no lot to take it from yet.
    }

    /** FEFO: soonest expiry first, no-expiry lots last, then oldest. FIFO: oldest first. */
    public static function inRotationOrder($query, ShopProduct $product)
    {
        if ($product->stock_rotation === 'fifo') {
            return $query->orderBy('received_on')->orderBy('created_at');
        }

        return $query->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')->orderBy('received_on')->orderBy('created_at');
    }

    /**
     * Take expired lots out of stock (movement type "expired"). Run nightly by
     * shop:expire-lots, and for an item just before an order takes its stock,
     * so expired stock is never sold.
     *
     * @return int units written off
     */
    public static function writeOffExpired(?ShopProduct $product = null, ?ShopProductVariant $variant = null, ?string $storeId = null): int
    {
        $lots = ShopStockLot::where('qty_remaining', '>', 0)->whereNotNull('expiry_date')->whereDate('expiry_date', '<', today())
            ->when($product, fn ($q) => $q->where('product_id', $product->id)
                ->when($variant, fn ($v) => $v->where('variant_id', $variant->id), fn ($v) => $v->whereNull('variant_id')))
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->with(['product', 'variant'])
            ->get();

        $total = 0;
        foreach ($lots as $lot) {
            if (! $lot->product || ! $lot->product->track_stock) {
                continue;
            }
            $lotVariant = $lot->variant;
            $lotVariant?->setRelation('product', $lot->product);
            self::record($lot->product, $lotVariant, -$lot->qty_remaining, 'expired', [
                'from_lot_id' => $lot->id,
                'only_lot' => true,
                'reference' => $lot->lot_number,
                'note' => __('Expired :date — taken off sale automatically', ['date' => $lot->expiry_date->format('d/m/Y')]),
            ]);
            $total += $lot->qty_remaining;
        }

        return $total;
    }

    /** Average cost of what's on the shelf, from the lots that have a cost. */
    public static function averageCost(ShopProduct $product, ?ShopProductVariant $variant): ?float
    {
        $row = self::lotsQuery($product, $variant)->where('qty_remaining', '>', 0)->whereNotNull('unit_cost')
            ->selectRaw('SUM(qty_remaining * unit_cost) as value, SUM(qty_remaining) as units')->first();

        return $row && (int) $row->units > 0 ? round((float) $row->value / (int) $row->units, 2) : null;
    }
}
