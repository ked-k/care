<?php

namespace App\Services\Shop;

use App\Models\Shop\ShopGrn;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductVariant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Batch 42: posting and reversing goods received notes.
 *
 * Receiving a GRN puts every line into stock as its own lot (lot number,
 * expiry, unit cost), turns stock tracking on for any product that wasn't
 * tracked yet, and resets each item's cost price to the average cost of what
 * is now on the shelf.
 */
class GoodsReceiving
{
    /**
     * @return array<int, string> names of products whose stock tracking this switched on
     *
     * @throws RuntimeException with a staff-facing message
     */
    public static function receive(ShopGrn $grn, ?int $userId): array
    {
        return DB::transaction(function () use ($grn, $userId) {
            $grn = ShopGrn::whereKey($grn->id)->lockForUpdate()->first();
            if (! $grn->isDraft()) {
                throw new RuntimeException(__('This delivery has already been received.'));
            }
            $lines = $grn->lines()->get();
            if ($lines->isEmpty()) {
                throw new RuntimeException(__('Add at least one product before receiving.'));
            }

            $turnedOn = [];
            foreach ($lines as $line) {
                $product = ShopProduct::where('store_id', $grn->store_id)->find($line->product_id);
                if (! $product) {
                    throw new RuntimeException(__(':name is no longer in the shop. Remove the line and try again.', ['name' => $line->name]));
                }
                $variant = null;
                if ($product->has_variants) {
                    $variant = $line->variant_id ? $product->variants()->find($line->variant_id) : null;
                    if (! $variant) {
                        throw new RuntimeException(__('Choose which size/option of :name was delivered.', ['name' => $product->name]));
                    }
                    $variant->setRelation('product', $product);
                }
                if ($product->track_expiry && ! $line->expiry_date) {
                    throw new RuntimeException(__(':name needs an expiry date.', ['name' => $line->name]));
                }

                if (! $product->track_stock) {
                    $product->update(['track_stock' => true]);
                    $turnedOn[$product->id] = $product->name;
                }

                $movement = ($variant ?? $product)->adjustStock((int) $line->qty, 'receipt', $grn->number,
                    $grn->supplier_reference ? __('Supplier ref :ref', ['ref' => $grn->supplier_reference]) : null,
                    null, $userId, [
                        'grn_id' => $grn->id,
                        'lot' => [
                            'lot_number' => $line->lot_number,
                            'expiry_date' => $line->expiry_date?->toDateString(),
                            'unit_cost' => $line->unit_cost !== null ? (float) $line->unit_cost : null,
                            'grn_line_id' => $line->id,
                            'received_on' => $grn->received_on->toDateString(),
                            'source' => 'grn',
                        ],
                    ]);

                $lotId = $movement->allocations()->value('lot_id');
                $line->update(['lot_id' => $lotId]);

                if ($line->unit_cost !== null) {
                    $avg = StockLedger::averageCost($product, $variant);
                    if ($avg !== null) {
                        $variant ? $variant->update(['cost_price' => $avg]) : $product->update(['cost_price' => $avg]);
                    }
                }
            }

            $grn->update([
                'status' => 'received',
                'received_by' => $userId,
                'received_at' => now(),
                'total_cost' => round((float) $lines->sum('line_total'), 2),
            ]);
            return array_values($turnedOn);
        });
    }

    /**
     * Undo a delivery entered by mistake. Only while every lot it created is
     * still untouched — once any of it has been sold or adjusted, correct it
     * with a stock adjustment instead.
     *
     * @throws RuntimeException
     */
    public static function reverse(ShopGrn $grn, string $reason, ?int $userId): ShopGrn
    {
        return DB::transaction(function () use ($grn, $reason, $userId) {
            $grn = ShopGrn::whereKey($grn->id)->lockForUpdate()->first();
            if ($grn->status !== 'received') {
                throw new RuntimeException(__('Only a received delivery can be reversed.'));
            }

            $lines = $grn->lines()->with('lot')->get();
            $used = $lines->filter(fn ($l) => ! $l->lot || $l->lot->qty_remaining !== $l->lot->qty_received);
            if ($used->isNotEmpty()) {
                throw new RuntimeException(__('Some of this delivery has already been sold or adjusted (:items). Correct it with a stock adjustment instead.', [
                    'items' => $used->pluck('name')->take(3)->implode(', '),
                ]));
            }

            foreach ($lines as $line) {
                $product = ShopProduct::withTrashed()->find($line->product_id);
                $variant = $line->variant_id ? ShopProductVariant::find($line->variant_id) : null;
                $variant?->setRelation('product', $product);
                ($variant ?? $product)->adjustStock(-(int) $line->qty, 'adjustment', $grn->number, __('Delivery reversed: :reason', ['reason' => $reason]), null, $userId, [
                    'grn_id' => $grn->id, 'from_lot_id' => $line->lot_id, 'only_lot' => true,
                ]);
            }

            $grn->update(['status' => 'reversed', 'reversed_by' => $userId, 'reversed_at' => now(), 'reversal_reason' => $reason]);

            return $grn;
        });
    }
}
