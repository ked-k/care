<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopGrn;
use App\Models\Shop\ShopSupplier;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/** Batch 42: every delivery received (goods received notes), newest first. */
class GoodsReceivedIndexComponent extends ShopAdminComponent
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $supplier = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status', 'supplier'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $store = $this->store();
        $grns = ShopGrn::where('store_id', $store->id)
            ->with(['supplier', 'receiver'])
            ->withCount('lines')->withSum('lines', 'qty')
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->supplier, fn ($q) => $q->where('supplier_id', $this->supplier))
            ->when(trim($this->search) !== '', function ($q) {
                $like = '%'.trim($this->search).'%';
                $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('supplier_reference', 'like', $like)
                    ->orWhere('purchase_order', 'like', $like)
                    ->orWhereHas('lines', fn ($l) => $l->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('lot_number', 'like', $like)));
            })
            ->orderByDesc('received_on')->orderByDesc('created_at')
            ->paginate(25);

        $month = ShopGrn::where('store_id', $store->id)->where('status', 'received')->where('received_on', '>=', now()->startOfMonth()->toDateString());

        return $this->adminPage('livewire.shop.admin.receiving', [
            'grns' => $grns,
            'suppliers' => ShopSupplier::where('store_id', $store->id)->orderBy('name')->get(),
            'drafts' => ShopGrn::where('store_id', $store->id)->where('status', 'draft')->count(),
            'monthCount' => (clone $month)->count(),
            'monthValue' => (float) (clone $month)->sum('total_cost'),
        ], __('Goods received'));
    }
}
