<div>
    @php
        $in = 'w-full rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm text-gray-700 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100';
        $inErr = 'w-full rounded-lg border border-accent-400 bg-white px-2.5 py-1.5 text-sm text-gray-700 outline-none';
        $statusColor = ['draft' => 'warning', 'received' => 'success', 'reversed' => 'gray'];
    @endphp

    <x-page-header title="{{ $grn ? __('Delivery :n', ['n' => $grn->number]) : __('Receive a delivery') }}"
                   subtitle="{{ $grn && ! $editable ? __('Received :date by :name', ['date' => $grn->received_at?->format('d M Y, H:i'), 'name' => $grn->receiver?->name ?? '—']) : __('Goods received note — everything that came in on one delivery') }}"
                   icon="ik ik-download"
                   :breadcrumbs="['Shop' => route('shop.admin.dashboard'), 'Goods received' => route('shop.admin.receiving'), ($grn ? $grn->number : __('New')) => null]">
        <div class="flex flex-wrap items-center gap-2">
            @if ($grn)
                <x-badge color="{{ $statusColor[$grn->status] ?? 'gray' }}">{{ $grn->statusLabel() }}</x-badge>
                <x-button variant="outline" href="{{ route('shop.admin.receiving.print', $grn->id) }}" target="_blank"><i class="ik ik-printer"></i>{{ __('Print') }}</x-button>
            @endif
            @if ($editable)
                <x-button variant="outline" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft,receive"><i class="ik ik-save"></i>{{ __('Save draft') }}</x-button>
                <x-button variant="success" wire:click="receive" wire:confirm="{{ __('Add these items to stock now? You can’t edit the delivery afterwards.') }}" wire:loading.attr="disabled" wire:target="saveDraft,receive">
                    <i class="ik ik-check"></i>{{ __('Receive stock') }}
                </x-button>
            @elseif ($grn && $grn->status === 'received')
                <x-button variant="ghost" @click="$dispatch('open-modal', 'reverse-grn')"><i class="ik ik-rotate-ccw"></i>{{ __('Reverse') }}</x-button>
            @endif
        </div>
    </x-page-header>

    @if ($errors->any())
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-accent-400 bg-accent-500/5 px-4 py-3 text-sm text-accent-600" role="alert">
            <i class="ik ik-alert-circle mt-0.5"></i>
            <span>{{ $errors->first() }} @if ($errors->count() > 1)({{ __('and :n more', ['n' => $errors->count() - 1]) }})@endif</span>
        </div>
    @endif

    @if ($grn && $grn->status === 'reversed')
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
            <i class="ik ik-rotate-ccw mt-0.5"></i>
            <span>{{ __('Reversed :date by :name — :reason', ['date' => $grn->reversed_at?->format('d M Y, H:i'), 'name' => $grn->reverser?->name ?? '—', 'reason' => $grn->reversal_reason]) }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        {{-- ============ Delivery details ============ --}}
        <x-card title="{{ __('Delivery details') }}" icon="ik ik-truck">
            @if ($editable)
                <div class="space-y-4">
                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="form.supplier_id" class="text-sm font-medium text-gray-700">{{ __('Supplier') }}</label>
                            <button type="button" @click="$dispatch('open-modal', 'new-supplier')" class="text-xs font-medium text-primary-600 hover:underline"><i class="ik ik-plus"></i> {{ __('New supplier') }}</button>
                        </div>
                        <select id="form.supplier_id" wire:model="form.supplier_id" class="{{ $in }}">
                            <option value="">{{ __('— Not recorded —') }}</option>
                            @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                    </div>
                    <x-form.input name="form.supplier_reference" label="{{ __('Delivery note / invoice no.') }}" wire:model="form.supplier_reference" />
                    <x-form.input name="form.purchase_order" label="{{ __('Your order (PO) number') }}" wire:model="form.purchase_order" hint="{{ __('Optional') }}" />
                    <x-form.input type="date" name="form.received_on" label="{{ __('Date received') }}" wire:model="form.received_on" max="{{ today()->toDateString() }}" required />
                    <x-form.textarea name="form.notes" label="{{ __('Notes') }}" wire:model="form.notes" rows="3" placeholder="{{ __('e.g. 1 carton damaged, supplier credit requested') }}" />
                </div>
            @else
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-xs uppercase tracking-wide text-gray-400">{{ __('Supplier') }}</dt><dd class="font-medium text-gray-700">{{ $grn->supplier?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-gray-400">{{ __('Delivery note / invoice no.') }}</dt><dd class="text-gray-700">{{ $grn->supplier_reference ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-gray-400">{{ __('Your order (PO) number') }}</dt><dd class="text-gray-700">{{ $grn->purchase_order ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-wide text-gray-400">{{ __('Date received') }}</dt><dd class="text-gray-700">{{ $grn->received_on->format('d M Y') }}</dd></div>
                    @if ($grn->notes)<div><dt class="text-xs uppercase tracking-wide text-gray-400">{{ __('Notes') }}</dt><dd class="whitespace-pre-line text-gray-700">{{ $grn->notes }}</dd></div>@endif
                    <div><dt class="text-xs uppercase tracking-wide text-gray-400">{{ __('Entered by') }}</dt><dd class="text-gray-700">{{ $grn->creator?->name ?? '—' }}</dd></div>
                </dl>
            @endif
        </x-card>

        {{-- ============ Lines ============ --}}
        {{-- Totals are worked out in the browser while typing (no server round trip
             that could overwrite a field mid-typing); the server recalculates on save. --}}
        <div class="lg:col-span-2"
             x-data="{ fmt(n) { return new Intl.NumberFormat('en-GB', { style: 'currency', currency: @js($store->currency ?: 'GBP') }).format(n) },
                       lineTotal(i) { const l = $wire.lines[i] || {}; return l.unit_cost === '' || l.unit_cost == null ? null : (parseFloat(l.unit_cost) || 0) * (parseInt(l.qty) || 0) },
                       units() { return ($wire.lines || []).reduce((s, l) => s + (parseInt(l.qty) || 0), 0) },
                       value() { return ($wire.lines || []).reduce((s, l, i) => s + (this.lineTotal(i) || 0), 0) } }">
        <x-card no-padding title="{{ __('Items received') }}" subtitle="{{ trans_choice(':count line|:count lines', count($lines), ['count' => count($lines)]) }}{{ $editable ? '' : ' · '.trans_choice(':count unit|:count units', $units, ['count' => number_format($units)]).' · '.$store->money($value) }}" icon="ik ik-package">
            @if ($editable)
                <div class="relative border-b border-gray-100 p-4" x-data @click.outside="$wire.set('find', '')">
                    <div class="relative">
                        <i class="ik ik-maximize pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="search" id="grn-find" wire:model.live.debounce.300ms="find" wire:keydown.enter.prevent="scan" autocomplete="off" autofocus
                               placeholder="{{ __('Scan a barcode, or type a product name or code…') }}"
                               class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-700 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                    </div>
                    @if (mb_strlen(trim($find)) >= 2)
                        <div class="absolute left-4 right-4 top-full z-20 -mt-2 max-h-80 overflow-y-auto rounded-xl border border-gray-100 bg-white py-1 shadow-xl">
                            @forelse ($results as $r)
                                <button type="button" wire:click="addItem('{{ $r['product_id'] }}', {{ $r['variant_id'] ? "'".$r['variant_id']."'" : 'null' }})"
                                        class="flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm hover:bg-gray-50">
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-gray-700">{{ $r['name'] }}</span>
                                        <span class="font-mono text-xs text-gray-400">{{ $r['sku'] ?: '—' }}</span>
                                    </span>
                                    <span class="shrink-0 text-xs text-gray-400">{{ $r['stock'] === null ? __('not tracked') : __(':n in stock', ['n' => $r['stock']]) }}</span>
                                </button>
                            @empty
                                <p class="px-4 py-3 text-sm text-gray-400">{{ __('No product matches “:q”.', ['q' => $find]) }}</p>
                            @endforelse
                        </div>
                    @endif
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-4 py-3 font-medium">{{ __('Product') }}</th>
                            <th class="px-3 py-3 font-medium">{{ __('Qty') }}</th>
                            <th class="px-3 py-3 font-medium">{{ __('Unit cost') }}</th>
                            <th class="px-3 py-3 font-medium">{{ __('Lot / batch') }}</th>
                            <th class="px-3 py-3 font-medium">{{ __('Expiry') }}</th>
                            <th class="px-3 py-3 text-right font-medium">{{ __('Total') }}</th>
                            @if ($editable)<th class="px-3 py-3"></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($lines as $i => $line)
                            @php $lineTotal = $line['unit_cost'] === '' ? null : (float) $line['unit_cost'] * (int) $line['qty']; @endphp
                            <tr wire:key="grn-line-{{ $line['key'] }}" class="align-top">
                                <td class="px-4 py-2.5">
                                    <span class="block max-w-xs font-medium text-gray-700">{{ $line['name'] }}</span>
                                    <span class="font-mono text-xs text-gray-400">{{ $line['sku'] ?: '—' }}</span>
                                    @if ($line['track_expiry'])<span class="ml-1 rounded bg-amber-50 px-1.5 text-xs text-amber-800">{{ __('expiry required') }}</span>@endif
                                </td>
                                @if ($editable)
                                    <td class="px-3 py-2"><input type="number" min="1" wire:model="lines.{{ $i }}.qty" class="{{ $errors->has("lines.$i.qty") ? $inErr : $in }} w-20" aria-label="{{ __('Quantity') }}"></td>
                                    <td class="px-3 py-2"><input type="number" step="0.01" min="0" wire:model="lines.{{ $i }}.unit_cost" placeholder="0.00" class="{{ $errors->has("lines.$i.unit_cost") ? $inErr : $in }} w-24" aria-label="{{ __('Unit cost') }}"></td>
                                    <td class="px-3 py-2"><input type="text" wire:model="lines.{{ $i }}.lot_number" class="{{ $in }} w-32" aria-label="{{ __('Lot or batch number') }}"></td>
                                    <td class="px-3 py-2">
                                        <input type="date" wire:model="lines.{{ $i }}.expiry_date" min="{{ today()->toDateString() }}" class="{{ $errors->has("lines.$i.expiry_date") ? $inErr : $in }} w-36" aria-label="{{ __('Expiry date') }}">
                                        @error("lines.$i.expiry_date")<p class="mt-1 w-36 text-xs text-accent-600">{{ $message }}</p>@enderror
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold text-gray-700" x-text="lineTotal({{ $i }}) === null ? '—' : fmt(lineTotal({{ $i }}))">{{ $lineTotal !== null ? $store->money($lineTotal) : '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-right">
                                        <button type="button" wire:click="splitLine({{ $i }})" class="text-gray-400 hover:text-primary-600" title="{{ __('Another batch of this item (different lot or expiry)') }}"><i class="ik ik-copy"></i></button>
                                        <button type="button" wire:click="removeLine({{ $i }})" class="ml-2 text-gray-400 hover:text-accent-600" title="{{ __('Remove') }}"><i class="ik ik-trash-2"></i></button>
                                    </td>
                                @else
                                    @php $lot = $grn?->lines[$i]?->lot ?? null; @endphp
                                    <td class="px-3 py-2.5 font-semibold text-gray-700">{{ $line['qty'] }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-gray-600">{{ $line['unit_cost'] !== '' ? $store->money($line['unit_cost']) : '—' }}</td>
                                    <td class="px-3 py-2.5 text-gray-600">
                                        {{ $line['lot_number'] ?: '—' }}
                                        @if ($lot && $lot->qty_remaining !== $lot->qty_received)<span class="block text-xs text-gray-400">{{ __(':n of :m left', ['n' => $lot->qty_remaining, 'm' => $lot->qty_received]) }}</span>@endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-gray-600">{{ $line['expiry_date'] ? \Illuminate\Support\Carbon::parse($line['expiry_date'])->format('d/m/Y') : '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-right font-semibold text-gray-700">{{ $lineTotal !== null ? $store->money($lineTotal) : '—' }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10">
                                <x-empty-state compact title="{{ __('No items yet') }}" description="{{ __('Scan each product’s barcode, or search for it above. Scanning the same item again adds one more.') }}" icon="ik ik-maximize" />
                            </td></tr>
                        @endforelse
                    </tbody>
                    @if (count($lines))
                        <tfoot>
                            <tr class="border-t border-gray-100 text-sm">
                                <td class="px-4 py-3 font-semibold text-gray-700">{{ __('Total') }}</td>
                                <td class="px-3 py-3 font-semibold text-gray-700" @if ($editable) x-text="units()" @endif>{{ number_format($units) }}</td>
                                <td colspan="3"></td>
                                <td class="whitespace-nowrap px-3 py-3 text-right font-bold text-gray-800" @if ($editable) x-text="fmt(value())" @endif>{{ $store->money($value) }}</td>
                                @if ($editable)<td></td>@endif
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @if ($editable)
                <p class="border-t border-gray-100 px-4 py-3 text-xs text-gray-400">
                    {{ __('Nothing changes in stock until you press Receive stock. Each line becomes its own batch, sold soonest-expiry first (FEFO) or oldest first (FIFO), as set on the product. Products that weren’t tracking stock start tracking.') }}
                </p>
            @endif
        </x-card>
        </div>
    </div>

    @if ($editable && $grn)
        <div class="mt-5">
            <x-button variant="ghost" class="text-accent-600" wire:click="deleteDraft" wire:confirm="{{ __('Delete this draft delivery?') }}"><i class="ik ik-trash-2"></i>{{ __('Delete draft') }}</x-button>
        </div>
    @endif

    <x-modal name="new-supplier" title="{{ __('New supplier') }}">
        <form wire:submit="addSupplier" id="new-supplier-form">
            <x-form.input name="newSupplier" label="{{ __('Supplier name') }}" wire:model="newSupplier" required />
            <p class="mt-2 text-xs text-gray-400">{{ __('Add contact details later under Suppliers.') }}</p>
        </form>
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-modal', 'new-supplier')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="new-supplier-form">{{ __('Add supplier') }}</x-button>
        </x-slot:footer>
    </x-modal>

    <x-modal name="reverse-grn" title="{{ __('Reverse this delivery?') }}">
        <form wire:submit="reverse" id="reverse-grn-form" class="space-y-3">
            <p class="text-sm text-gray-600">{{ __('Takes every item on this delivery back out of stock. Only possible while none of it has been sold or adjusted.') }}</p>
            <x-form.input name="reverseReason" label="{{ __('Reason') }}" wire:model="reverseReason" placeholder="{{ __('e.g. Entered twice by mistake') }}" required />
        </form>
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-modal', 'reverse-grn')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="reverse-grn-form" variant="danger">{{ __('Reverse delivery') }}</x-button>
        </x-slot:footer>
    </x-modal>
</div>
