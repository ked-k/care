<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Products list with search, filters and bulk publish/unpublish for working
 * through an imported catalogue quickly. Creating and editing happen on the
 * product page (ProductFormComponent, Batch 41).
 */
class ProductIndexComponent extends ShopAdminComponent
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $categoryFilter = '';

    #[Url(except: '')]
    public string $statusFilter = '';

    #[Url(except: '')]
    public string $stockFilter = '';

    public array $selected = [];

    public function updated($property): void
    {
        if (in_array($property, ['search', 'categoryFilter', 'statusFilter', 'stockFilter'], true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    /** Batch 40 links used ?create; the editor is its own page since Batch 41. */
    public function create()
    {
        return $this->redirectRoute('shop.admin.products.create', navigate: true);
    }

    public function bulkStatus(string $status): void
    {
        abort_unless(in_array($status, ['draft', 'active', 'archived'], true), 422);
        if (empty($this->selected)) {
            return;
        }
        $query = $this->store()->products()->whereIn('id', $this->selected);
        $skipped = 0;
        if ($status === 'active') {
            // Never publish something at £0 — it would be free to buy.
            $skipped = (clone $query)->where('price', '<=', 0)->count();
            $query->where('price', '>', 0);
        }
        $count = $query->update(['status' => $status, 'updated_by' => Auth::id()]);
        $this->selected = [];
        $this->toast($skipped
            ? __(':n updated; :s skipped because they have no price.', ['n' => $count, 's' => $skipped])
            : __(':n products updated.', ['n' => $count]), $skipped ? 'warning' : 'success');
    }

    /** Batch 42: switch stock tracking on/off for many products at once. */
    public function bulkTracking(string $mode): void
    {
        abort_unless(in_array($mode, ['track', 'track_backorder', 'untrack', 'expiry'], true), 422);
        if (empty($this->selected)) {
            return;
        }
        $data = match ($mode) {
            'track' => ['track_stock' => true, 'allow_backorder' => false],
            'track_backorder' => ['track_stock' => true, 'allow_backorder' => true],
            'untrack' => ['track_stock' => false],
            'expiry' => ['track_stock' => true, 'track_expiry' => true, 'stock_rotation' => 'fefo'],
        };
        $count = $this->store()->products()->whereIn('id', $this->selected)->update($data + ['updated_by' => Auth::id()]);
        $this->selected = [];
        $this->toast(match ($mode) {
            'untrack' => __(':n products no longer track stock — always available.', ['n' => $count]),
            'expiry' => __(':n products now track batches and expiry dates (sold soonest-expiry first).', ['n' => $count]),
            default => __(':n products now track stock. Receive a delivery to put stock on them.', ['n' => $count]),
        });
    }

    public function selectPage(array $ids): void
    {
        $this->selected = count(array_diff($ids, $this->selected)) ? array_values(array_unique(array_merge($this->selected, $ids))) : array_values(array_diff($this->selected, $ids));
    }

    public function render()
    {
        $store = $this->store();
        $products = $store->products()
            ->with(['category', 'images', 'tiers', 'variants.tiers'])
            ->search($this->search)
            ->when($this->categoryFilter === 'none', fn ($q) => $q->whereNull('category_id'))
            ->when($this->categoryFilter && $this->categoryFilter !== 'none', fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->stockFilter === 'low', fn ($q) => $q->where('track_stock', true)->where(fn ($w) => $w
                ->where(fn ($s) => $s->where('has_variants', false)->whereColumn('stock_qty', '<=', 'low_stock_threshold')->where('stock_qty', '>', 0))
                ->orWhereHas('variants', fn ($v) => $v->where('is_active', true)->where('stock_qty', '>', 0)
                    ->whereRaw('shop_product_variants.stock_qty <= COALESCE(shop_product_variants.low_stock_threshold, shop_products.low_stock_threshold)'))))
            ->when($this->stockFilter === 'out', fn ($q) => $q->where('track_stock', true)->where(fn ($w) => $w
                ->where(fn ($s) => $s->where('has_variants', false)->where('stock_qty', '<=', 0))
                ->orWhereHas('variants', fn ($v) => $v->where('is_active', true)->where('stock_qty', '<=', 0))))
            ->orderBy('name')
            ->paginate((int) request('per_page', 25));

        return $this->adminPage('livewire.shop.admin.products', [
            'products' => $products,
            'categories' => ShopCategory::where('store_id', $store->id)->orderBy('name')->get(),
            'statusCounts' => $store->products()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status'),
        ], __('Products'));
    }
}
