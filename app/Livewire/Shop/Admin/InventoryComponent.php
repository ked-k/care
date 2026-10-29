<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductVariant;
use App\Models\Shop\ShopLotAllocation;
use App\Models\Shop\ShopStockLot;
use App\Models\Shop\ShopStockMovement;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Stock levels, adjustments, batches & expiry, and the full stock ledger.
 * Batch 41: a product with sizes/options lists each option underneath it,
 * and stock is adjusted per option.
 * Batch 42: deliveries are received on a goods received note (Receive opens
 * one with the item on it); the Batches tab lists every lot on the shelf with
 * its expiry, lets you write a lot off, and traces it to the orders it went
 * out on (for recalls).
 */
class InventoryComponent extends ShopAdminComponent
{
    use WithPagination;

    #[Url(except: 'levels')]
    public string $tab = 'levels';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'low')]
    public string $show = 'low';

    #[Url(except: 'stock')]
    public string $lotFilter = 'stock';

    public ?string $adjustingId = null;
    public ?string $adjustingVariantId = null;
    public string $adjustType = 'adjustment';
    public string $adjustQty = '';
    public string $adjustReference = '';
    public string $adjustNote = '';
    public string $adjustLot = '';
    public string $adjustExpiry = '';
    public string $adjustFromLot = '';

    public ?string $lotId = null;
    public string $writeOffQty = '';
    public string $writeOffReason = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'show', 'tab', 'lotFilter'], true)) {
            $this->resetPage();
        }
    }

    /** Receiving now happens on a goods received note, with this item already on it. */
    public function receive(string $productId, ?string $variantId = null)
    {
        return $this->redirectRoute('shop.admin.receiving.create', ['add' => $productId.($variantId ? ':'.$variantId : '')], navigate: true);
    }

    public function openAdjust(string $productId, string $type = 'adjustment', ?string $variantId = null): void
    {
        $this->resetErrorBag();
        $product = $this->store()->products()->findOrFail($productId);
        if ($product->has_variants) {
            abort_unless($variantId && $product->variants()->whereKey($variantId)->exists(), 404);
        }
        $this->adjustingId = $productId;
        $this->adjustingVariantId = $product->has_variants ? $variantId : null;
        $this->adjustType = $type;
        $this->adjustQty = '';
        $this->adjustReference = '';
        $this->adjustNote = '';
        $this->adjustLot = '';
        $this->adjustExpiry = '';
        $this->adjustFromLot = '';
        $this->dispatch('open-drawer', 'stock-adjust');
    }

    public function saveAdjust(): void
    {
        $this->validate([
            'adjustType' => 'required|in:adjustment,return',
            'adjustLot' => 'nullable|string|max:80',
            'adjustExpiry' => 'nullable|date',
            'adjustFromLot' => 'nullable|uuid',
            'adjustQty' => $this->adjustType === 'adjustment' ? 'required|integer|not_in:0' : 'required|integer|min:1',
            'adjustReference' => 'nullable|string|max:120',
            'adjustNote' => $this->adjustType === 'adjustment' ? 'required|string|max:255' : 'nullable|string|max:255',
        ], [
            'adjustNote.required' => __('Say why the stock is being adjusted (e.g. damaged, stock count).'),
            'adjustQty.not_in' => __('Enter a positive or negative number.'),
        ]);

        $product = $this->store()->products()->findOrFail($this->adjustingId);
        $item = $this->adjustingVariantId ? $product->variants()->findOrFail($this->adjustingVariantId) : $product;
        $qty = (int) $this->adjustQty;
        if ($qty > 0 && $product->track_expiry && $this->adjustExpiry === '') {
            $this->addError('adjustExpiry', __('This product tracks expiry dates — enter the expiry of the stock being added.'));

            return;
        }
        if ($item instanceof ShopProductVariant) {
            $item->setRelation('product', $product);
        }
        $extra = $qty > 0
            ? ['lot' => ['lot_number' => trim($this->adjustLot) ?: null, 'expiry_date' => $this->adjustExpiry ?: null]]
            : ['from_lot_id' => $this->adjustFromLot ?: null];
        $item->adjustStock($qty, $this->adjustType, $this->adjustReference ?: null, $this->adjustNote ?: null, null, Auth::id(), $extra);

        $name = $item instanceof ShopProductVariant ? $product->name.' — '.$item->title() : $product->name;
        $this->dispatch('close-drawer', 'stock-adjust');
        $this->toast(__(':name now has :n in stock.', ['name' => $name, 'n' => $item->stock_qty]));
    }

    /* ---------- Batches ---------- */

    private function lot(string $id): ShopStockLot
    {
        return ShopStockLot::where('store_id', $this->store()->id)->with(['product', 'variant', 'grn'])->findOrFail($id);
    }

    public function openLot(string $id): void
    {
        $lot = $this->lot($id);
        $this->resetErrorBag();
        $this->lotId = $lot->id;
        $this->writeOffQty = (string) $lot->qty_remaining;
        $this->writeOffReason = $lot->isExpired() ? __('Expired') : '';
        $this->dispatch('open-drawer', 'lot-detail');
    }

    public function writeOff(): void
    {
        $lot = $this->lot((string) $this->lotId);
        $this->validate([
            'writeOffQty' => 'required|integer|min:1|max:'.max(1, $lot->qty_remaining),
            'writeOffReason' => 'required|string|max:255',
        ], ['writeOffReason.required' => __('Say why (expired, damaged, recalled…).')]);

        $variant = $lot->variant;
        $variant?->setRelation('product', $lot->product);
        ($variant ?? $lot->product)->adjustStock(-(int) $this->writeOffQty, $lot->isExpired() ? 'expired' : 'adjustment', $lot->lot_number,
            __('Batch written off: :reason', ['reason' => $this->writeOffReason]), null, Auth::id(), ['from_lot_id' => $lot->id, 'only_lot' => true]);

        $this->dispatch('close-drawer', 'lot-detail');
        $this->toast(__(':n written off from :lot.', ['n' => $this->writeOffQty, 'lot' => $lot->label()]));
    }

    public function showExpiring(): void
    {
        $this->tab = 'batches';
        $this->lotFilter = 'soon';
        $this->resetPage('lotsPage');
    }

    public function setWarningDays(int $days): void
    {
        $this->store()->update(['expiry_warning_days' => max(7, min(365, $days))]);
    }

    public function render()
    {
        $store = $this->store();

        // A variant counts as low when at or under its own warning level (or the product's).
        $variantLow = fn ($v) => $v->where('is_active', true)
            ->whereRaw('shop_product_variants.stock_qty <= COALESCE(shop_product_variants.low_stock_threshold, shop_products.low_stock_threshold)');

        $products = $store->products()->where('track_stock', true)->with(['category', 'variants'])
            ->search($this->search)
            ->when($this->show === 'low', fn ($q) => $q->where(fn ($w) => $w
                ->where(fn ($s) => $s->where('has_variants', false)->whereColumn('stock_qty', '<=', 'low_stock_threshold'))
                ->orWhereHas('variants', $variantLow)))
            ->when($this->show === 'active', fn ($q) => $q->where('status', 'active'))
            ->orderBy('stock_qty')->orderBy('name')
            ->paginate(25, ['*'], 'levelsPage');

        $movements = $this->tab === 'history'
            ? ShopStockMovement::whereHas('product', fn ($q) => $q->withTrashed()->where('store_id', $store->id))
                ->with(['product', 'variant', 'user', 'allocations.lot', 'grn'])
                ->when($this->search !== '', fn ($q) => $q->whereHas('product', fn ($p) => $p->withTrashed()->search($this->search)))
                ->latest()->paginate(30, ['*'], 'historyPage')
            : null;

        // Units and value: single products, plus every option of products that have them.
        $simple = $store->products()->where('track_stock', true)->where('has_variants', false)->where('stock_qty', '>', 0);
        $variants = ShopProductVariant::query()->join('shop_products', 'shop_products.id', '=', 'shop_product_variants.product_id')
            ->where('shop_products.store_id', $store->id)->whereNull('shop_products.deleted_at')
            ->where('shop_products.track_stock', true)->where('shop_products.has_variants', true);

        $summary = [
            'value' => (float) (clone $simple)->selectRaw('SUM(stock_qty * COALESCE(cost_price, 0)) as v')->value('v')
                + (float) (clone $variants)->where('shop_product_variants.stock_qty', '>', 0)
                    ->selectRaw('SUM(shop_product_variants.stock_qty * COALESCE(shop_product_variants.cost_price, shop_products.cost_price, 0)) as v')->value('v'),
            'units' => (int) (clone $simple)->sum('stock_qty')
                + (int) (clone $variants)->where('shop_product_variants.stock_qty', '>', 0)->sum('shop_product_variants.stock_qty'),
            'low' => $store->products()->active()->where('track_stock', true)->where('has_variants', false)->whereColumn('stock_qty', '<=', 'low_stock_threshold')->where('stock_qty', '>', 0)->count()
                + (clone $variants)->where('shop_products.status', 'active')->where('shop_product_variants.is_active', true)->where('shop_product_variants.stock_qty', '>', 0)
                    ->whereRaw('shop_product_variants.stock_qty <= COALESCE(shop_product_variants.low_stock_threshold, shop_products.low_stock_threshold)')->count(),
            'out' => $store->products()->active()->where('track_stock', true)->where('has_variants', false)->where('stock_qty', '<=', 0)->where('allow_backorder', false)->count()
                + (clone $variants)->where('shop_products.status', 'active')->where('shop_products.allow_backorder', false)
                    ->where('shop_product_variants.is_active', true)->where('shop_product_variants.stock_qty', '<=', 0)->count(),
        ];

        $warn = (int) ($store->expiry_warning_days ?: 60);
        $lots = $this->tab === 'batches'
            ? ShopStockLot::where('store_id', $store->id)->with(['product', 'variant', 'grn'])
                ->whereHas('product', fn ($q) => $q->where('track_stock', true))
                ->when($this->lotFilter === 'stock', fn ($q) => $q->where('qty_remaining', '>', 0))
                ->when($this->lotFilter === 'soon', fn ($q) => $q->where('qty_remaining', '>', 0)->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<=', today()->addDays($warn)))
                ->when($this->lotFilter === 'expired', fn ($q) => $q->whereNotNull('expiry_date')->whereDate('expiry_date', '<', today()))
                ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($w) => $w->where('lot_number', 'like', '%'.trim($this->search).'%')
                    ->orWhereHas('product', fn ($p) => $p->search($this->search))))
                ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')->orderBy('expiry_date')->orderBy('received_on')
                ->paginate(30, ['*'], 'lotsPage')
            : null;
        $summary['expiring'] = ShopStockLot::where('store_id', $store->id)->where('qty_remaining', '>', 0)->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', today()->addDays($warn))->whereHas('product', fn ($q) => $q->where('track_stock', true))->count();

        $lot = $this->lotId ? ShopStockLot::where('store_id', $store->id)->with(['product', 'variant', 'grn.supplier'])->find($this->lotId) : null;
        $trace = $lot
            ? ShopLotAllocation::where('lot_id', $lot->id)->with(['movement.user', 'order'])->latest()->get()
            : collect();

        $adjusting = $this->adjustingId ? ShopProduct::find($this->adjustingId) : null;
        $adjustLots = $adjusting && $adjusting->track_stock
            ? ShopStockLot::where('product_id', $adjusting->id)
                ->when($this->adjustingVariantId, fn ($q) => $q->where('variant_id', $this->adjustingVariantId), fn ($q) => $q->whereNull('variant_id'))
                ->where('qty_remaining', '>', 0)->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')->orderBy('expiry_date')->get()
            : collect();
        $adjustingVariant = $this->adjustingVariantId ? ShopProductVariant::find($this->adjustingVariantId) : null;

        return $this->adminPage('livewire.shop.admin.inventory', compact('products', 'movements', 'summary', 'adjusting', 'adjustingVariant', 'lots', 'lot', 'trace', 'warn', 'adjustLots'), __('Inventory'));
    }
}
