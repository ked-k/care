<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopGrn;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductVariant;
use App\Models\Shop\ShopSupplier;
use App\Services\Shop\GoodsReceiving;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use RuntimeException;

/**
 * Batch 42: a goods received note — record one delivery from a supplier with
 * as many products as came in. Scan a barcode or type a name/code to add a
 * line; each line has quantity, unit cost, lot/batch number and expiry.
 *
 * Saving keeps it as a draft (nothing changes in stock). "Receive stock"
 * posts every line into stock as a lot (GoodsReceiving::receive()).
 */
class GoodsReceivedFormComponent extends ShopAdminComponent
{
    #[Locked]
    public ?string $grnId = null;

    public array $form = [];
    /** @var array<int, array<string, mixed>> */
    public array $lines = [];
    public string $find = '';
    public string $newSupplier = '';
    public string $reverseReason = '';

    public function mount(?string $grnId = null): void
    {
        $this->form = [
            'supplier_id' => '', 'supplier_reference' => '', 'purchase_order' => '',
            'received_on' => today()->toDateString(), 'notes' => '',
        ];

        if ($grnId) {
            $grn = ShopGrn::where('store_id', $this->store()->id)->with('lines')->findOrFail($grnId);
            $this->grnId = $grn->id;
            $this->form = [
                'supplier_id' => (string) $grn->supplier_id,
                'supplier_reference' => (string) $grn->supplier_reference,
                'purchase_order' => (string) $grn->purchase_order,
                'received_on' => $grn->received_on->toDateString(),
                'notes' => (string) $grn->notes,
            ];
            $trackExpiry = ShopProduct::withTrashed()->whereIn('id', $grn->lines->pluck('product_id'))->pluck('track_expiry', 'id');
            $this->lines = $grn->lines->map(fn ($l) => [
                'key' => Str::random(8),
                'product_id' => $l->product_id,
                'variant_id' => $l->variant_id,
                'name' => $l->name,
                'sku' => (string) $l->sku,
                'qty' => (string) $l->qty,
                'unit_cost' => $l->unit_cost !== null ? rtrim(rtrim((string) $l->unit_cost, '0'), '.') : '',
                'lot_number' => (string) $l->lot_number,
                'expiry_date' => $l->expiry_date?->toDateString() ?? '',
                'track_expiry' => (bool) ($trackExpiry[$l->product_id] ?? false),
            ])->all();

            return;
        }

        // "Receive" from Inventory or a product: ?add=<product id>[:<variant id>]
        if ($add = request()->query('add')) {
            [$productId, $variantId] = array_pad(explode(':', (string) $add, 2), 2, null);
            $this->addItem($productId, $variantId);
        }
        if ($supplier = request()->query('supplier')) {
            $this->form['supplier_id'] = (string) ShopSupplier::where('store_id', $this->store()->id)->whereKey($supplier)->value('id');
        }
    }

    private function grn(): ?ShopGrn
    {
        return $this->grnId ? ShopGrn::where('store_id', $this->store()->id)->find($this->grnId) : null;
    }

    private function editable(): bool
    {
        return ! $this->grnId || $this->grn()?->isDraft();
    }

    /* ---------- Adding lines ---------- */

    /** Enter in the search box: an exact code/barcode adds the item at once (barcode scanners send Enter). */
    public function scan(): void
    {
        $term = trim($this->find);
        if ($term === '' || ! $this->editable()) {
            return;
        }
        $storeId = $this->store()->id;

        $variant = ShopProductVariant::where(fn ($q) => $q->where('sku', $term)->orWhere('barcode', $term))
            ->whereHas('product', fn ($q) => $q->where('store_id', $storeId))->first();
        if ($variant) {
            $this->addItem($variant->product_id, $variant->id);
            $this->find = '';

            return;
        }
        $product = ShopProduct::where('store_id', $storeId)->where('has_variants', false)
            ->where(fn ($q) => $q->where('sku', $term)->orWhere('barcode', $term))->first();
        if ($product) {
            $this->addItem($product->id);
            $this->find = '';

            return;
        }

        $results = $this->results();
        if (count($results) === 1) {
            $this->addItem($results[0]['product_id'], $results[0]['variant_id']);
            $this->find = '';
        }
    }

    public function addItem(?string $productId, ?string $variantId = null): void
    {
        if (! $this->editable() || ! $productId) {
            return;
        }
        $product = ShopProduct::where('store_id', $this->store()->id)->find($productId);
        if (! $product) {
            return;
        }
        $variant = null;
        if ($product->has_variants) {
            $variant = $variantId ? $product->variants()->find($variantId) : null;
            if (! $variant) {
                $this->toast(__('Pick the size or option of :name that was delivered.', ['name' => $product->name]), 'warning');

                return;
            }
            $variant->setRelation('product', $product);
        }

        // Scanning the same item twice adds one more — unless it's a different batch.
        foreach ($this->lines as $i => $line) {
            if ($line['product_id'] === $product->id && $line['variant_id'] === $variant?->id && $line['lot_number'] === '' && $line['expiry_date'] === '') {
                $this->lines[$i]['qty'] = (string) ((int) $line['qty'] + 1);
                $this->toast(__(':name: now :n', ['name' => $line['name'], 'n' => $this->lines[$i]['qty']]));

                return;
            }
        }

        $cost = $variant ? $variant->costPrice() : ($product->cost_price !== null ? (float) $product->cost_price : null);
        $this->lines[] = [
            'key' => Str::random(8),
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'name' => $variant ? $product->name.' — '.$variant->title() : $product->name,
            'sku' => (string) ($variant?->sku ?: $product->sku),
            'qty' => '1',
            'unit_cost' => $cost !== null ? number_format($cost, 2, '.', '') : '',
            'lot_number' => '',
            'expiry_date' => '',
            'track_expiry' => (bool) $product->track_expiry,
        ];
        $this->find = '';
    }

    /** Same product again on a new line, for a second batch with a different lot/expiry. */
    public function splitLine(int $i): void
    {
        if (! isset($this->lines[$i]) || ! $this->editable()) {
            return;
        }
        $copy = array_merge($this->lines[$i], ['key' => Str::random(8), 'qty' => '1', 'lot_number' => '', 'expiry_date' => '']);
        array_splice($this->lines, $i + 1, 0, [$copy]);
    }

    public function removeLine(int $i): void
    {
        if ($this->editable()) {
            unset($this->lines[$i]);
            $this->lines = array_values($this->lines);
        }
    }

    /** Search results for the box above the lines: products, and each size/option of those that have them. */
    private function results(): array
    {
        $term = trim($this->find);
        if (mb_strlen($term) < 2) {
            return [];
        }
        $words = preg_split('/\s+/', mb_strtolower($term));
        $products = $this->store()->products()->search($term)->with('variants')->orderBy('name')->limit(15)->get();

        $out = [];
        foreach ($products as $p) {
            if (! $p->has_variants) {
                $out[] = ['product_id' => $p->id, 'variant_id' => null, 'name' => $p->name, 'sku' => $p->sku, 'stock' => $p->track_stock ? $p->stock_qty : null];

                continue;
            }
            foreach ($p->variantList() as $v) {
                $hay = mb_strtolower($p->name.' '.$v->title().' '.$v->sku.' '.$v->barcode);
                // "gloves large" shows only the large ones; "gloves" shows every size.
                if (collect($words)->every(fn ($w) => str_contains($hay, $w))) {
                    $out[] = ['product_id' => $p->id, 'variant_id' => $v->id, 'name' => $p->name.' — '.$v->title(), 'sku' => $v->sku, 'stock' => $p->track_stock ? $v->stock_qty : null];
                }
            }
        }

        return array_slice($out, 0, 20);
    }

    /* ---------- Suppliers ---------- */

    public function addSupplier(): void
    {
        $name = trim($this->newSupplier);
        if ($name === '') {
            return;
        }
        $supplier = ShopSupplier::firstOrCreate(['store_id' => $this->store()->id, 'name' => $name], ['is_active' => true]);
        $this->form['supplier_id'] = $supplier->id;
        $this->newSupplier = '';
        $this->closeModal('new-supplier');
    }

    /** x-modal listens for a plain string; a server dispatch would arrive as an array. */
    private function closeModal(string $name): void
    {
        $this->js("window.dispatchEvent(new CustomEvent('close-modal', { detail: '{$name}' }))");
    }

    /* ---------- Save / receive / reverse ---------- */

    private function rules(bool $receiving): array
    {
        $storeId = $this->store()->id;

        return [
            'form.supplier_id' => ['nullable', \Illuminate\Validation\Rule::exists('shop_suppliers', 'id')->where('store_id', $storeId)],
            'form.supplier_reference' => 'nullable|string|max:120',
            'form.purchase_order' => 'nullable|string|max:120',
            'form.received_on' => 'required|date|before_or_equal:today',
            'form.notes' => 'nullable|string|max:2000',
            'lines' => $receiving ? 'required|array|min:1' : 'array',
            'lines.*.qty' => 'required|integer|min:1|max:1000000',
            'lines.*.unit_cost' => 'nullable|numeric|min:0|max:999999',
            'lines.*.lot_number' => 'nullable|string|max:80',
            'lines.*.expiry_date' => 'nullable|date',
        ];
    }

    private function persist(bool $receiving): ShopGrn
    {
        $this->validate($this->rules($receiving), [
            'lines.required' => __('Add at least one product.'),
            'lines.min' => __('Add at least one product.'),
            'form.received_on.before_or_equal' => __('The date received can’t be in the future.'),
        ], [
            'lines.*.qty' => __('quantity'),
            'lines.*.unit_cost' => __('unit cost'),
            'lines.*.expiry_date' => __('expiry date'),
        ]);

        if ($receiving) {
            $errors = [];
            foreach ($this->lines as $i => $line) {
                if ($line['track_expiry'] && $line['expiry_date'] === '') {
                    $errors["lines.$i.expiry_date"] = __(':name needs an expiry date.', ['name' => $line['name']]);
                } elseif ($line['expiry_date'] !== '' && Carbon::parse($line['expiry_date'])->lt(today())) {
                    $errors["lines.$i.expiry_date"] = __(':name: that expiry date has already passed.', ['name' => $line['name']]);
                }
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
        }

        return DB::transaction(function () {
            $store = $this->store();
            $data = [
                'supplier_id' => $this->form['supplier_id'] ?: null,
                'supplier_reference' => trim($this->form['supplier_reference']) ?: null,
                'purchase_order' => trim($this->form['purchase_order']) ?: null,
                'received_on' => $this->form['received_on'],
                'notes' => trim($this->form['notes']) ?: null,
            ];

            $grn = $this->grn();
            if ($grn) {
                abort_unless($grn->isDraft(), 422);
                $grn->update($data);
            } else {
                $grn = ShopGrn::create($data + [
                    'store_id' => $store->id,
                    'number' => ShopGrn::nextNumber($store),
                    'status' => 'draft',
                    'created_by' => Auth::id(),
                ]);
                $this->grnId = $grn->id;
            }

            $grn->lines()->delete();
            $total = 0.0;
            foreach (array_values($this->lines) as $i => $line) {
                $cost = $line['unit_cost'] === '' ? null : (float) $line['unit_cost'];
                $lineTotal = $cost !== null ? round($cost * (int) $line['qty'], 2) : 0;
                $total += $lineTotal;
                $grn->lines()->create([
                    'product_id' => $line['product_id'],
                    'variant_id' => $line['variant_id'],
                    'name' => $line['name'],
                    'sku' => $line['sku'] ?: null,
                    'qty' => (int) $line['qty'],
                    'unit_cost' => $cost,
                    'line_total' => $lineTotal,
                    'lot_number' => trim($line['lot_number']) ?: null,
                    'expiry_date' => $line['expiry_date'] ?: null,
                    'sort_order' => $i,
                ]);
            }
            $grn->update(['total_cost' => round($total, 2)]);

            return $grn;
        });
    }

    public function saveDraft()
    {
        if (! $this->editable()) {
            return null;
        }
        $grn = $this->persist(false);
        session()->flash('success', __(':n saved as a draft. Stock changes when you receive it.', ['n' => $grn->number]));

        return $this->redirectRoute('shop.admin.receiving.show', ['grnId' => $grn->id], navigate: true);
    }

    public function receive()
    {
        if (! $this->editable()) {
            return null;
        }
        $grn = $this->persist(true);
        try {
            $turnedOn = GoodsReceiving::receive($grn, Auth::id());
        } catch (RuntimeException $e) {
            $this->toast($e->getMessage(), 'error');

            return null;
        }

        $units = array_sum(array_map(fn ($l) => (int) $l['qty'], $this->lines));
        $message = __(':n received — :units units added to stock.', ['n' => $grn->number, 'units' => number_format($units)]);
        if ($turnedOn) {
            $message .= ' '.__('Stock tracking switched on for: :names.', ['names' => implode(', ', array_slice($turnedOn, 0, 3)).(count($turnedOn) > 3 ? '…' : '')]);
        }
        session()->flash('success', $message);

        return $this->redirectRoute('shop.admin.receiving.show', ['grnId' => $grn->id], navigate: true);
    }

    public function reverse()
    {
        $this->validate(['reverseReason' => 'required|string|max:255'], ['reverseReason.required' => __('Say why this delivery is being reversed.')]);
        $grn = $this->grn();
        abort_unless($grn, 404);
        try {
            GoodsReceiving::reverse($grn, $this->reverseReason, Auth::id());
        } catch (RuntimeException $e) {
            $this->closeModal('reverse-grn');
            $this->toast($e->getMessage(), 'error');

            return null;
        }
        session()->flash('success', __(':n reversed — its stock has been taken back off.', ['n' => $grn->number]));

        return $this->redirectRoute('shop.admin.receiving.show', ['grnId' => $grn->id], navigate: true);
    }

    public function deleteDraft()
    {
        $grn = $this->grn();
        if ($grn && $grn->isDraft()) {
            $grn->delete();
            session()->flash('success', __('Draft deleted.'));
        }

        return $this->redirectRoute('shop.admin.receiving', navigate: true);
    }

    public function render()
    {
        $grn = $this->grn()?->load(['supplier', 'receiver', 'reverser', 'lines.lot']);
        $units = array_sum(array_map(fn ($l) => (int) $l['qty'], $this->lines));
        $value = array_sum(array_map(fn ($l) => $l['unit_cost'] === '' ? 0 : (float) $l['unit_cost'] * (int) $l['qty'], $this->lines));

        return $this->adminPage('livewire.shop.admin.receiving-form', [
            'grn' => $grn,
            'editable' => $this->editable(),
            'suppliers' => ShopSupplier::where('store_id', $this->store()->id)->where('is_active', true)->orderBy('name')->get(),
            'results' => $this->editable() ? $this->results() : [],
            'units' => $units,
            'value' => round($value, 2),
        ], $grn ? $grn->number : __('Receive a delivery'));
    }
}
