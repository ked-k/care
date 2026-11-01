<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\Contact;
use App\Models\Accounting\Document;
use App\Models\Accounting\Journal;
use App\Models\Shop\ShopGrn;
use App\Models\Shop\ShopLotAllocation;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopStockLot;
use App\Models\Shop\ShopStockMovement;
use App\Models\Shop\ShopStore;
use App\Models\Shop\ShopSupplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Batch 43: puts the Shop into the ledger.
 *
 *  - Order paid      → Dr takings account / Cr sales, delivery, VAT
 *  - Order refunded  → that journal reversed
 *  - Stock sold      → Dr cost of sales / Cr inventory, at the cost of the
 *                      actual batches (lots) it came out of (FEFO/FIFO)
 *  - Stock adjusted, expired, returned → inventory against write-offs
 *  - Delivery (GRN) received → a supplier bill for its cost
 *                      (Dr inventory / Cr payables), or Dr inventory /
 *                      Cr "goods received not invoiced" when no supplier
 *  - GRN reversed    → that bill voided
 */
class ShopPosting
{
    private static array $agencyByStore = [];

    public static function agencyForStore(?string $storeId): ?string
    {
        if (! $storeId) {
            return null;
        }

        return self::$agencyByStore[$storeId] ??= ShopStore::whereKey($storeId)->value('agency_id');
    }

    public static function syncOrder(ShopOrder $order): void
    {
        $agencyId = self::agencyForStore($order->store_id);
        if (! $agencyId) {
            return;
        }
        $settings = AccountingSetting::forAgency($agencyId);
        if (! $settings->autoPosts('shop_sales')) {
            return;
        }
        $sale = Journal::where('agency_id', $agencyId)->where('source_type', 'shop_order')
            ->where('source_id', $order->id)->where('source_event', 'paid')->first();

        $paidStates = ['paid', 'refunded'];
        if (! $sale && in_array($order->payment_status, $paidStates, true)) {
            $paidOn = Carbon::parse($order->paid_at ?? $order->updated_at);
            if ($settings->beforePostingStart($paidOn)) {
                return;
            }
            $total = round((float) $order->total, 2);
            if ($total <= 0) {
                return;
            }
            $vat = round((float) $order->vat_total, 2);
            $delivery = round(min((float) $order->delivery_price, $total - $vat), 2);
            $goods = round($total - $vat - $delivery, 2);
            if ($goods < 0) {
                $delivery += $goods;
                $goods = 0;
            }
            $takings = match ($order->payment_method) {
                'stripe', 'paypal', 'card' => 'card_clearing',
                'pay_on_delivery', 'cod', 'cash' => 'cash',
                default => 'bank',
            };
            $sale = Ledger::post($agencyId, [
                'date' => $settings->isLocked($paidOn) ? today() : $paidOn,
                'type' => 'shop_sale',
                'reference' => $order->number,
                'memo' => __('Shop order :n — :e', ['n' => $order->number, 'e' => $order->email]),
                'source_type' => 'shop_order', 'source_id' => $order->id, 'source_event' => 'paid',
                'system' => true,
            ], [
                ['account' => $takings, 'debit' => $total, 'description' => __('Order :n (:m)', ['n' => $order->number, 'm' => $order->payment_method])],
                ['account' => 'shop_sales', 'credit' => $goods, 'description' => __('Order :n', ['n' => $order->number])],
                ['account' => 'delivery_income', 'credit' => $delivery, 'description' => $order->delivery_name ?: __('Delivery')],
                ['account' => 'vat', 'credit' => $vat, 'description' => __('VAT on :n', ['n' => $order->number])],
            ]);
        }

        if ($sale && ! $sale->isReversed() && $order->payment_status === 'refunded') {
            Ledger::reverse($sale, __('Order refunded'), today(), 'refunded');
        }
    }

    /** What a movement did to the stock's value at cost (+ in, − out). */
    public static function movementValue(ShopStockMovement $movement): float
    {
        $value = 0.0;
        $allocs = ShopLotAllocation::where('movement_id', $movement->id)->get();
        $costs = ShopStockLot::whereIn('id', $allocs->pluck('lot_id'))->pluck('unit_cost', 'id');
        foreach ($allocs as $a) {
            $value -= (int) $a->qty * (float) ($costs[$a->lot_id] ?? 0);
        }

        return round($value, 2);
    }

    public static function syncMovement(ShopStockMovement $movement): void
    {
        // Deliveries and their reversals are posted from the GRN itself.
        if ($movement->grn_id || (int) $movement->qty === 0) {
            return;
        }
        $storeId = DB::table('shop_products')->where('id', $movement->product_id)->value('store_id');
        $agencyId = self::agencyForStore($storeId);
        if (! $agencyId) {
            return;
        }
        $settings = AccountingSetting::forAgency($agencyId);
        if (! $settings->autoPosts('shop_stock') || $settings->beforePostingStart($movement->created_at)) {
            return;
        }
        $value = self::movementValue($movement);
        if (abs($value) < 0.005) {
            return;
        }
        $counter = in_array($movement->type, ['sale', 'cancellation', 'return'], true) ? 'cogs' : 'stock_adjustments';
        $name = $movement->product?->name ?? __('Stock');
        $label = ShopStockMovement::TYPE_LABELS[$movement->type] ?? ucfirst($movement->type);
        $date = Carbon::parse($movement->created_at);

        Ledger::post($agencyId, [
            'date' => $settings->isLocked($date) ? today() : $date,
            'type' => 'stock',
            'reference' => $movement->reference,
            'memo' => __(':what: :qty × :name', ['what' => __($label), 'qty' => abs((int) $movement->qty), 'name' => $name]),
            'source_type' => 'shop_stock_movement', 'source_id' => $movement->id, 'source_event' => 'moved',
            'system' => true,
        ], [
            ['account' => 'inventory', 'debit' => $value, 'description' => $name],
            ['account' => $counter, 'credit' => $value, 'description' => __($label)],
        ]);
    }

    public static function syncGrn(ShopGrn $grn): void
    {
        $agencyId = self::agencyForStore($grn->store_id);
        if (! $agencyId) {
            return;
        }
        $settings = AccountingSetting::forAgency($agencyId);
        if (! $settings->autoPosts('shop_stock') || ! in_array($grn->status, ['received', 'reversed'], true)) {
            return;
        }
        $receivedOn = Carbon::parse($grn->received_on ?? $grn->received_at ?? $grn->created_at);
        if ($settings->beforePostingStart($receivedOn)) {
            return;
        }
        $total = round((float) $grn->total_cost, 2);

        $bill = Document::where('agency_id', $agencyId)->where('source_type', 'shop_grn')->where('source_id', $grn->id)->first();
        $journal = Journal::where('agency_id', $agencyId)->where('source_type', 'shop_grn')
            ->where('source_id', $grn->id)->where('source_event', 'received')->first();

        if (! $bill && ! $journal && $total > 0) {
            $date = $settings->isLocked($receivedOn) ? today() : $receivedOn;
            if ($grn->supplier_id && ($supplier = ShopSupplier::find($grn->supplier_id))) {
                $contact = self::supplierContact($supplier, $agencyId);
                $bill = Documents::saveDraft(new Document([
                    'agency_id' => $agencyId, 'kind' => 'bill', 'status' => 'draft',
                    'number' => AccountingSetting::takeNumber($agencyId, 'bill'),
                    'source_type' => 'shop_grn', 'source_id' => $grn->id, 'created_by' => $grn->received_by,
                ]), $agencyId, 'bill', [
                    'contact_id' => $contact->id,
                    'issue_date' => $date->toDateString(),
                    'due_date' => $date->copy()->addDays($contact->payment_terms_days ?? $settings->payment_terms_days)->toDateString(),
                    'reference' => trim(implode(' / ', array_filter([$grn->supplier_reference, $grn->purchase_order, $grn->number]))),
                    'notes' => __('Created from shop delivery :n', ['n' => $grn->number]),
                ], [[
                    'account_id' => $settings->account('inventory')->id,
                    'description' => __('Stock received — :n (:c lines)', ['n' => $grn->number, 'c' => $grn->lines()->count()]),
                    'qty' => 1, 'unit_price' => $total, 'vat_rate' => 0,
                ]], $grn->received_by);
                Documents::post($bill, $grn->received_by);
            } else {
                $journal = Ledger::post($agencyId, [
                    'date' => $date, 'type' => 'grn', 'reference' => $grn->number,
                    'memo' => __('Stock received with no supplier — :n', ['n' => $grn->number]),
                    'source_type' => 'shop_grn', 'source_id' => $grn->id, 'source_event' => 'received', 'system' => true,
                ], [
                    ['account' => 'inventory', 'debit' => $total, 'description' => $grn->number],
                    ['account' => 'grni', 'credit' => $total, 'description' => $grn->number],
                ]);
            }
        }

        if ($grn->status === 'reversed') {
            if ($bill && $bill->status !== 'void') {
                Documents::void($bill, __('Delivery :n reversed: :r', ['n' => $grn->number, 'r' => $grn->reversal_reason]), $grn->reversed_by);
            }
            if ($journal && ! $journal->isReversed()) {
                Ledger::reverse($journal, __('Delivery reversed'), $grn->reversed_at ?? today());
            }
        }
    }

    public static function supplierContact(ShopSupplier $supplier, string $agencyId): Contact
    {
        $contact = Contact::where('agency_id', $agencyId)->where('shop_supplier_id', $supplier->id)->first()
            ?? Contact::where('agency_id', $agencyId)->where('is_supplier', true)->whereRaw('lower(name) = ?', [mb_strtolower($supplier->name)])->first();
        if ($contact) {
            $contact->shop_supplier_id ??= $supplier->id;
            $contact->is_supplier = true;
            $contact->save();

            return $contact;
        }

        return Contact::create([
            'agency_id' => $agencyId, 'is_supplier' => true, 'name' => $supplier->name,
            'contact_name' => $supplier->contact_name, 'email' => $supplier->email, 'phone' => $supplier->phone,
            'address' => $supplier->address, 'shop_supplier_id' => $supplier->id,
            'notes' => $supplier->account_number ? __('Our account number with them: :n', ['n' => $supplier->account_number]) : null,
        ]);
    }

    /** Shop stock at cost, from the batches (lots) still on the shelf. */
    public static function stockValue(string $agencyId): float
    {
        $storeIds = ShopStore::where('agency_id', $agencyId)->pluck('id');
        if ($storeIds->isEmpty()) {
            return 0;
        }

        return round((float) ShopStockLot::whereIn('store_id', $storeIds)
            ->where('qty_remaining', '>', 0)->selectRaw('sum(qty_remaining * coalesce(unit_cost, 0)) as v')->value('v'), 2);
    }

    /**
     * One-off "opening stock" journal so the Inventory account starts equal
     * to the stock actually on the shelf: whatever the shop held before
     * posting began is brought in against Opening balances.
     */
    public static function openingStock(string $agencyId): ?Journal
    {
        $settings = AccountingSetting::forAgency($agencyId);
        if (Journal::where('agency_id', $agencyId)->where('source_type', 'shop_opening_stock')->exists()) {
            return null;
        }
        $inventory = $settings->account('inventory');
        $ledger = round((float) DB::table('acc_journal_lines')->where('account_id', $inventory->id)->sum(DB::raw('debit - credit')), 2);
        $gap = round(self::stockValue($agencyId) - $ledger, 2);
        if (abs($gap) < 0.01) {
            return null;
        }
        $date = $settings->posting_from ?? today();
        if ($settings->isLocked($date)) {
            $date = $settings->lock_date->copy()->addDay();
        }

        return Ledger::post($agencyId, [
            'date' => $date, 'type' => 'opening',
            'reference' => __('Opening stock'),
            'memo' => __('Shop stock on hand at cost when the ledger started'),
            'source_type' => 'shop_opening_stock', 'source_id' => $agencyId, 'source_event' => 'opening', 'system' => true,
        ], [
            ['account' => $inventory, 'debit' => $gap, 'description' => __('Opening stock')],
            ['account' => 'opening_balance', 'credit' => $gap, 'description' => __('Opening stock')],
        ]);
    }
}
