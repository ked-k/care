<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopOrder;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderIndexComponent extends ShopAdminComponent
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $payment = '';

    public function updated($property): void
    {
        $this->resetPage();
    }

    private function query()
    {
        return ShopOrder::where('store_id', $this->store()->id)
            ->when($this->search !== '', function ($q) {
                $like = '%'.$this->search.'%';
                $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('email', 'like', $like)
                    ->orWhere('billing_address->name', 'like', $like)->orWhere('billing_address->postcode', 'like', $like));
            })
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->payment !== '', fn ($q) => $q->where('payment_status', $this->payment))
            ->latest();
    }

    public function exportCsv(): StreamedResponse
    {
        $store = $this->store();
        $rows = $this->query()->with('items')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order', 'Date', 'Customer', 'Email', 'Postcode', 'Items', 'Subtotal', 'Discount', 'Delivery', 'VAT', 'Total', 'Payment method', 'Payment status', 'Status']);
            foreach ($rows as $o) {
                fputcsv($out, [
                    $o->number, $o->created_at->format('Y-m-d H:i'), $o->billing_address['name'] ?? '', $o->email,
                    $o->shipping_address['postcode'] ?? ($o->billing_address['postcode'] ?? ''), $o->items->sum('qty'),
                    $o->subtotal, $o->discount_total, $o->delivery_price, $o->vat_total, $o->total,
                    $o->paymentMethodLabel(), $o->paymentStatusLabel(), $o->statusLabel(),
                ]);
            }
            fclose($out);
        }, 'shop-orders-'.$store->slug.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $base = ShopOrder::where('store_id', $this->store()->id);
        $counts = [
            '' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'processing' => (clone $base)->where('status', 'processing')->count(),
            'shipped' => (clone $base)->where('status', 'shipped')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'cancelled' => (clone $base)->whereIn('status', ['cancelled', 'refunded'])->count(),
        ];

        return $this->adminPage('livewire.shop.admin.orders', [
            'orders' => $this->query()->withCount('items')->paginate((int) request('per_page', 25)),
            'counts' => $counts,
        ], __('Orders'));
    }
}
