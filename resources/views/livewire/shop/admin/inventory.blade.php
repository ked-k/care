<div>
    <x-page-header title="{{ __('Inventory') }}" subtitle="{{ __('Stock levels, batches and expiry, adjustments and history') }}" icon="ik ik-layers"
                   :breadcrumbs="['Shop' => route('shop.admin.dashboard'), 'Inventory' => null]">
        <x-button href="{{ route('shop.admin.receiving.create') }}" wire:navigate><i class="ik ik-download"></i>{{ __('Receive a delivery') }}</x-button>
    </x-page-header>

    @if ($summary['expiring'] > 0)
        <button type="button" wire:click="showExpiring"
                class="mb-5 flex w-full items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-left text-sm text-amber-800 transition hover:shadow-sm">
            <i class="ik ik-clock"></i>
            <span class="flex-1">{{ trans_choice(':count batch expires within :days days, or already has.|:count batches expire within :days days, or already have.', $summary['expiring'], ['count' => $summary['expiring'], 'days' => $warn]) }}</span>
            <span class="font-medium">{{ __('Review') }} <i class="ik ik-chevron-right"></i></span>
        </button>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card variant="soft" value="{{ number_format($summary['units']) }}" label="{{ __('Units in stock') }}" icon="ik ik-layers" color="primary" />
        <x-stat-card variant="soft" value="{{ $store->money($summary['value']) }}" label="{{ __('Stock value at cost') }}" icon="ik ik-dollar-sign" color="green" />
        <x-stat-card variant="soft" value="{{ number_format($summary['low']) }}" label="{{ __('Running low') }}" icon="ik ik-alert-triangle" color="amber" />
        <x-stat-card variant="soft" value="{{ number_format($summary['out']) }}" label="{{ __('Out of stock (on sale)') }}" icon="ik ik-slash" color="accent" />
    </div>

    <div class="mb-4 flex gap-1 border-b border-gray-200">
        @foreach (['levels' => __('Stock levels'), 'batches' => __('Batches & expiry'), 'history' => __('Stock history')] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')" @class([
                '-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition',
                'border-primary-500 text-primary-600' => $tab === $key,
                'border-transparent text-gray-500 hover:text-gray-700' => $tab !== $key,
            ])>{{ $label }}</button>
        @endforeach
    </div>

    @if ($tab === 'levels')
        <x-table :paginator="$products" :exports="false" :print="false">
            <x-slot:filters>
                <div class="relative">
                    <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="search" wire:model.live.debounce.400ms="search" placeholder="{{ __('Name or SKU') }}"
                           class="w-64 rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500">
                </div>
                <select wire:model.live="show" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500" aria-label="{{ __('Show') }}">
                    <option value="low">{{ __('Low or out of stock') }}</option>
                    <option value="active">{{ __('Products on sale') }}</option>
                    <option value="all">{{ __('Everything tracked') }}</option>
                </select>
            </x-slot:filters>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Product') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('In stock') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Warn at') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($products as $p)
                        @if ($p->has_variants)
                            <tr class="bg-gray-50/60" wire:key="inv-{{ $p->id }}">
                                <td class="px-5 py-3">
                                    <span class="block max-w-md truncate font-medium text-gray-700">{{ $p->name }}</span>
                                    <span class="text-xs text-gray-400">{{ $p->variantSummary() }}</span>
                                    @if ($p->supplier_name)<span class="ml-1 text-xs text-gray-400">— {{ $p->supplier_name }}</span>@endif
                                </td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-600">{{ $p->stock_qty }} <span class="block text-xs font-normal text-gray-400">{{ __('total') }}</span></td>
                                <td class="px-5 py-3 text-right text-gray-400">{{ $p->low_stock_threshold }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ ['active' => __('On sale'), 'draft' => __('Draft'), 'archived' => __('Archived')][$p->status] }}</td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('shop.admin.products.edit', $p->id) }}" wire:navigate class="text-sm text-gray-400 hover:text-gray-600" title="{{ __('Edit product') }}"><i class="ik ik-edit-2"></i></a>
                                </td>
                            </tr>
                            @foreach ($p->variantList() as $v)
                                @php $threshold = $v->lowStockThreshold(); @endphp
                                <tr class="hover:bg-gray-50" wire:key="inv-{{ $p->id }}-{{ $v->id }}">
                                    <td class="py-2.5 pl-10 pr-5">
                                        <span class="block font-medium text-gray-700"><i class="ik ik-corner-down-right mr-1 text-gray-300"></i>{{ $v->title() }}</span>
                                        <span class="pl-3 font-mono text-xs text-gray-400">{{ $v->sku ?: '—' }}</span>
                                        @unless ($v->is_active)<span class="ml-1 text-xs text-gray-400">({{ __('not on sale') }})</span>@endunless
                                    </td>
                                    <td class="px-5 py-2.5 text-right">
                                        <x-badge color="{{ $v->stock_qty <= 0 ? 'danger' : ($v->stock_qty <= $threshold ? 'warning' : 'success') }}">{{ $v->stock_qty }}</x-badge>
                                    </td>
                                    <td class="px-5 py-2.5 text-right text-gray-500">{{ $threshold }}</td>
                                    <td class="px-5 py-2.5"></td>
                                    <td class="whitespace-nowrap px-5 py-2.5 text-right">
                                        <x-button size="sm" variant="outline" wire:click="receive('{{ $p->id }}', '{{ $v->id }}')"><i class="ik ik-download"></i>{{ __('Receive') }}</x-button>
                                        <x-button size="sm" variant="ghost" wire:click="openAdjust('{{ $p->id }}', 'adjustment', '{{ $v->id }}')">{{ __('Adjust') }}</x-button>
                                        @if ($url = $v->supplier_url ?: $p->supplier_url)
                                            <a href="{{ $url }}" target="_blank" rel="noopener" class="ml-1 text-gray-400 hover:text-gray-600" title="{{ __('Supplier page') }}"><i class="ik ik-external-link"></i></a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr class="hover:bg-gray-50" wire:key="inv-{{ $p->id }}">
                                <td class="px-5 py-3">
                                    <span class="block max-w-md truncate font-medium text-gray-700">{{ $p->name }}</span>
                                    <span class="font-mono text-xs text-gray-400">{{ $p->sku }}</span>
                                    @if ($p->supplier_name)<span class="ml-1 text-xs text-gray-400">— {{ $p->supplier_name }}</span>@endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <x-badge color="{{ $p->stock_qty <= 0 ? 'danger' : ($p->stock_qty <= $p->low_stock_threshold ? 'warning' : 'success') }}">{{ $p->stock_qty }}</x-badge>
                                </td>
                                <td class="px-5 py-3 text-right text-gray-500">{{ $p->low_stock_threshold }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ ['active' => __('On sale'), 'draft' => __('Draft'), 'archived' => __('Archived')][$p->status] }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    <x-button size="sm" variant="outline" wire:click="receive('{{ $p->id }}')"><i class="ik ik-download"></i>{{ __('Receive') }}</x-button>
                                    <x-button size="sm" variant="ghost" wire:click="openAdjust('{{ $p->id }}', 'adjustment')">{{ __('Adjust') }}</x-button>
                                    @if ($p->supplier_url)
                                        <a href="{{ $p->supplier_url }}" target="_blank" rel="noopener" class="ml-1 text-gray-400 hover:text-gray-600" title="{{ __('Supplier page') }}"><i class="ik ik-external-link"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="px-5 py-10">
                            <x-empty-state title="{{ $show === 'low' ? __('Nothing low on stock') : __('No tracked products') }}" icon="ik ik-check-circle"
                                           description="{{ $show === 'low' ? __('Every tracked product is above its warning level.') : __('Turn on stock tracking on a product to see it here.') }}" />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    @elseif ($tab === 'batches')
        @include('livewire.shop.admin.partials.inventory-batches')
    @else
        <x-table :paginator="$movements" :exports="false" :print="false">
            <x-slot:filters>
                <div class="relative">
                    <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="search" wire:model.live.debounce.400ms="search" placeholder="{{ __('Product name or SKU') }}"
                           class="w-64 rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500">
                </div>
            </x-slot:filters>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('When') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Product') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('What') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Change') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Balance') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('By') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($movements as $m)
                        <tr wire:key="mv-{{ $m->id }}">
                            <td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ $m->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-5 py-3"><span class="block max-w-xs truncate text-gray-700">{{ $m->itemName() }}</span></td>
                            <td class="px-5 py-3 text-gray-600">
                                {{ $m->typeLabel() }}
                                @if ($m->reference)
                                    @if ($m->order_id)
                                        <a href="{{ route('shop.admin.orders.show', $m->order_id) }}" wire:navigate class="ml-1 font-mono text-xs text-primary-600 hover:underline">{{ $m->reference }}</a>
                                    @else
                                        <span class="ml-1 font-mono text-xs text-gray-400">{{ $m->reference }}</span>
                                    @endif
                                @endif
                                @if ($m->grn)<a href="{{ route('shop.admin.receiving.show', $m->grn_id) }}" wire:navigate class="ml-1 text-xs text-primary-600 hover:underline">{{ __('view delivery') }}</a>@endif
                                @if ($m->note)<span class="block text-xs text-gray-400">{{ $m->note }}</span>@endif
                                @php $lotsTouched = $m->allocations->filter(fn ($a) => $a->lot)->map(fn ($a) => $a->lot->lot_number ?: ($a->lot->expiry_date ? __('exp. :d', ['d' => $a->lot->expiry_date->format('d/m/Y')]) : null))->filter()->unique(); @endphp
                                @if ($lotsTouched->isNotEmpty())<span class="block text-xs text-gray-500"><i class="ik ik-tag"></i> {{ $lotsTouched->implode(', ') }}</span>@endif
                            </td>
                            <td class="px-5 py-3 text-right font-semibold {{ $m->qty > 0 ? 'text-green-600' : 'text-accent-600' }}">{{ $m->qty > 0 ? '+' : '' }}{{ $m->qty }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ $m->balance_after }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $m->user?->name ?? ($m->order_id ? __('Customer order') : '—') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10"><x-empty-state title="{{ __('No stock movements yet') }}" icon="ik ik-list" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    @endif

    <x-drawer name="stock-adjust" title="{{ $adjustType === 'return' ? __('Customer return') : __('Adjust stock') }}" width="w-[28rem]">
        @if ($adjusting)
            <form wire:submit="saveAdjust" id="stock-adjust-form" class="space-y-4">
                <div class="rounded-xl bg-gray-50 p-3">
                    <p class="font-medium text-gray-700">{{ $adjusting->name }}</p>
                    @if ($adjustingVariant)<p class="text-sm font-medium text-primary-600">{{ $adjustingVariant->title() }} <span class="font-mono text-xs text-gray-400">{{ $adjustingVariant->sku }}</span></p>@endif
                    <p class="text-sm text-gray-500">{{ __('Currently in stock') }}: <span class="font-semibold">{{ $adjustingVariant ? $adjustingVariant->stock_qty : $adjusting->stock_qty }}</span></p>
                </div>
                <x-form.select name="adjustType" label="{{ __('Type') }}" wire:model.live="adjustType">
                    <option value="adjustment">{{ __('Correction (count, damage, expiry)') }}</option>
                    <option value="return">{{ __('Returned by a customer') }}</option>
                </x-form.select>
                <p class="text-xs text-gray-400">{{ __('A delivery from a supplier?') }} <a href="{{ route('shop.admin.receiving.create', ['add' => $adjusting->id.($adjustingVariant ? ':'.$adjustingVariant->id : '')]) }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Record it as a delivery') }}</a> {{ __('so it gets its lot number, expiry and cost.') }}</p>
                <x-form.input type="number" name="adjustQty" label="{{ $adjustType === 'adjustment' ? __('Change (use a minus for stock removed)') : __('Quantity') }}" wire:model.live.debounce.400ms="adjustQty" required />
                @if ((int) $adjustQty > 0)
                    <div class="grid grid-cols-2 gap-3">
                        <x-form.input name="adjustLot" label="{{ __('Lot / batch no.') }}" wire:model="adjustLot" />
                        <x-form.input type="date" name="adjustExpiry" label="{{ $adjusting->track_expiry ? __('Expiry date') : __('Expiry (optional)') }}" wire:model="adjustExpiry" :required="$adjusting->track_expiry" />
                    </div>
                @elseif ((int) $adjustQty < 0 && $adjustLots->isNotEmpty())
                    <x-form.select name="adjustFromLot" label="{{ __('Take from batch') }}" wire:model="adjustFromLot">
                        <option value="">{{ $adjusting->stock_rotation === 'fifo' ? __('Oldest first (FIFO)') : __('Soonest expiry first (FEFO)') }}</option>
                        @foreach ($adjustLots as $l)<option value="{{ $l->id }}">{{ $l->label() }} — {{ __(':n left', ['n' => $l->qty_remaining]) }}</option>@endforeach
                    </x-form.select>
                @endif
                <x-form.input name="adjustReference" label="{{ __('Reference') }}" wire:model="adjustReference" placeholder="{{ $adjustType === 'return' ? __('Order number') : __('Stock count sheet, incident no.…') }}" />
                <x-form.input name="adjustNote" label="{{ $adjustType === 'adjustment' ? __('Reason') : __('Note (optional)') }}" wire:model="adjustNote" />
            </form>
        @endif
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'stock-adjust')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="stock-adjust-form">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
