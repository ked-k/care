<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopOrderItem;
use App\Models\Shop\ShopProduct;
use App\Services\Shop\Payments\PaymentManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShopDashboardComponent extends ShopAdminComponent
{
    public string $range = '30';

    public function render()
    {
        $store = $this->store();
        $days = (int) $this->range;
        $from = now()->subDays($days - 1)->startOfDay();
        $prevFrom = (clone $from)->subDays($days);

        $paid = fn () => ShopOrder::where('store_id', $store->id)->where('payment_status', 'paid');

        $revenue = (float) $paid()->where('paid_at', '>=', $from)->sum('total');
        $prevRevenue = (float) $paid()->whereBetween('paid_at', [$prevFrom, $from])->sum('total');
        $orders = ShopOrder::where('store_id', $store->id)->where('created_at', '>=', $from)->whereNotIn('status', ['cancelled'])->count();
        $paidCount = $paid()->where('paid_at', '>=', $from)->count();
        $avg = $paidCount ? $revenue / $paidCount : 0;

        // Daily paid revenue for the sparkline/chart
        $daily = $paid()->where('paid_at', '>=', $from)
            ->selectRaw('DATE(paid_at) as d, SUM(total) as t')->groupBy('d')->pluck('t', 'd');
        $series = collect(range(0, $days - 1))->map(fn ($i) => round((float) ($daily[Carbon::parse($from)->addDays($i)->toDateString()] ?? 0), 2));

        $needsAction = [
            'awaiting_payment' => ShopOrder::where('store_id', $store->id)->where('status', 'pending')->where('payment_status', 'unpaid')->count(),
            'to_dispatch' => ShopOrder::where('store_id', $store->id)->where('status', 'processing')->count(),
        ];

        // Single products and individual sizes/options at or under their warning level.
        $lowStock = ShopProduct::where('store_id', $store->id)->active()->where('track_stock', true)->where('has_variants', false)
            ->whereColumn('stock_qty', '<=', 'low_stock_threshold')->orderBy('stock_qty')->limit(8)->get(['name', 'stock_qty'])
            ->concat(\App\Models\Shop\ShopProductVariant::query()
                ->join('shop_products', 'shop_products.id', '=', 'shop_product_variants.product_id')
                ->where('shop_products.store_id', $store->id)->whereNull('shop_products.deleted_at')
                ->where('shop_products.status', 'active')->where('shop_products.track_stock', true)
                ->where('shop_product_variants.is_active', true)
                ->whereRaw('shop_product_variants.stock_qty <= COALESCE(shop_product_variants.low_stock_threshold, shop_products.low_stock_threshold)')
                ->orderBy('shop_product_variants.stock_qty')->limit(8)
                ->get(['shop_product_variants.*', 'shop_products.name as product_name'])
                ->map(fn ($v) => (object) ['name' => $v->product_name.' — '.$v->title(), 'stock_qty' => $v->stock_qty]))
            ->sortBy('stock_qty')->take(8)->values();

        $topProducts = ShopOrderItem::query()
            ->join('shop_orders', 'shop_orders.id', '=', 'shop_order_items.order_id')
            ->where('shop_orders.store_id', $store->id)->where('shop_orders.payment_status', 'paid')
            ->where('shop_orders.paid_at', '>=', $from)
            ->select('shop_order_items.name', DB::raw('SUM(shop_order_items.qty) as units'), DB::raw('SUM(shop_order_items.line_total) as revenue'))
            ->groupBy('shop_order_items.name')->orderByDesc('revenue')->limit(6)->get();

        $recent = ShopOrder::where('store_id', $store->id)->latest()->limit(8)->get();

        $setup = [
            ['done' => $store->products()->active()->exists(), 'label' => __('Publish some products'), 'route' => route('shop.admin.products')],
            ['done' => $store->deliveryMethods()->active()->exists(), 'label' => __('Add a delivery option'), 'route' => route('shop.admin.delivery')],
            ['done' => ! empty(PaymentManager::available($store)), 'label' => __('Turn on at least one way to pay'), 'route' => route('shop.admin.settings', ['tab' => 'payments'])],
            ['done' => filled($store->terms) && filled($store->returns_policy), 'label' => __('Write your terms and returns policy'), 'route' => route('shop.admin.settings', ['tab' => 'policies'])],
            ['done' => $store->is_live, 'label' => __('Open the shop to the public'), 'route' => route('shop.admin.settings', ['tab' => 'store'])],
        ];

        return $this->adminPage('livewire.shop.admin.dashboard', [
            'revenue' => $revenue,
            'revenueChange' => $prevRevenue > 0 ? round(($revenue - $prevRevenue) / $prevRevenue * 100, 1) : null,
            'orders' => $orders,
            'avg' => $avg,
            'series' => $series->all(),
            'needsAction' => $needsAction,
            'lowStock' => $lowStock,
            'topProducts' => $topProducts,
            'recent' => $recent,
            'setup' => $setup,
            'productCount' => $store->products()->count(),
            'activeCount' => $store->products()->active()->count(),
        ], __('Shop dashboard'));
    }
}
