<div>
    <x-page-header title="{{ __('Products') }}" subtitle="{{ __(':active on sale, :draft drafts', ['active' => number_format($statusCounts['active'] ?? 0), 'draft' => number_format($statusCounts['draft'] ?? 0)]) }}" icon="ik ik-package"
                   :breadcrumbs="['Shop' => route('shop.admin.dashboard'), 'Products' => null]">
        <x-button href="{{ route('shop.admin.products.create') }}" wire:navigate><i class="ik ik-plus"></i>{{ __('Add product') }}</x-button>
    </x-page-header>

    <x-table :paginator="$products" :exports="false" :print="false">
        <x-slot:filters>
            <div class="relative">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="{{ __('Name, SKU or brand') }}"
                       class="w-64 rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500">
            </div>
            <select wire:model.live="categoryFilter" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500" aria-label="{{ __('Category') }}">
                <option value="">{{ __('All categories') }}</option>
                <option value="none">{{ __('No category') }}</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500" aria-label="{{ __('Status') }}">
                <option value="">{{ __('Any status') }}</option>
                <option value="active">{{ __('On sale') }}</option>
                <option value="draft">{{ __('Draft') }}</option>
                <option value="archived">{{ __('Archived') }}</option>
            </select>
            <select wire:model.live="stockFilter" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500" aria-label="{{ __('Stock') }}">
                <option value="">{{ __('Any stock level') }}</option>
                <option value="low">{{ __('Low stock') }}</option>
                <option value="out">{{ __('Out of stock') }}</option>
            </select>
        </x-slot:filters>

        @if (count($selected))
            <div class="flex flex-wrap items-center gap-3 border-b border-primary-100 bg-primary-50 px-5 py-3">
                <span class="text-sm font-medium text-primary-600">{{ __(':n selected', ['n' => count($selected)]) }}</span>
                <button type="button" wire:click="$set('selected', [])" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Clear') }}</button>
                <div class="ml-auto flex flex-wrap gap-2">
                    <x-button size="sm" variant="success" wire:click="bulkStatus('active')"><i class="ik ik-eye"></i>{{ __('Put on sale') }}</x-button>
                    <x-button size="sm" variant="outline" wire:click="bulkStatus('draft')"><i class="ik ik-eye-off"></i>{{ __('Make draft') }}</x-button>
                    <x-button size="sm" variant="ghost" wire:click="bulkStatus('archived')"><i class="ik ik-archive"></i>{{ __('Archive') }}</x-button>
                    <x-dropdown width="w-72">
                        <x-slot:trigger>
                            <x-button size="sm" variant="outline"><i class="ik ik-layers"></i>{{ __('Stock tracking') }}<i class="ik ik-chevron-down"></i></x-button>
                        </x-slot:trigger>
                        <button type="button" wire:click="bulkTracking('track')" wire:confirm="{{ __('Track stock for the selected products? Any on sale with 0 in stock will show “Sold out” until you receive a delivery.') }}" class="block w-full px-4 py-2 text-left text-sm text-gray-600 hover:bg-gray-50">{{ __('Track stock — sell only what’s in stock') }}</button>
                        <button type="button" wire:click="bulkTracking('track_backorder')" class="block w-full px-4 py-2 text-left text-sm text-gray-600 hover:bg-gray-50">{{ __('Track stock — allow back orders') }}</button>
                        <button type="button" wire:click="bulkTracking('expiry')" wire:confirm="{{ __('Track batches and expiry dates for the selected products? Deliveries will then need an expiry date.') }}" class="block w-full px-4 py-2 text-left text-sm text-gray-600 hover:bg-gray-50">{{ __('Track batches & expiry (FEFO)') }}</button>
                        <button type="button" wire:click="bulkTracking('untrack')" class="block w-full px-4 py-2 text-left text-sm text-gray-600 hover:bg-gray-50">{{ __('Stop tracking — always available') }}</button>
                    </x-dropdown>
                </div>
            </div>
        @endif

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="w-10 px-5 py-3">
                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600" aria-label="{{ __('Select this page') }}"
                               wire:click="selectPage(@js($products->pluck('id')->all()))" @checked($products->isNotEmpty() && count(array_diff($products->pluck('id')->all(), $selected)) === 0)>
                    </th>
                    <th class="px-5 py-3 font-medium">{{ __('Product') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Category') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Price') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Stock') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($products as $p)
                    <tr class="hover:bg-gray-50" wire:key="p-{{ $p->id }}">
                        <td class="px-5 py-3"><input type="checkbox" value="{{ $p->id }}" wire:model.live="selected" class="h-4 w-4 rounded border-gray-300 text-primary-600" aria-label="{{ __('Select :name', ['name' => $p->name]) }}"></td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                @if ($img = $p->images->first())
                                    <img src="{{ $img->url() }}" alt="" class="h-10 w-10 shrink-0 rounded-lg border border-gray-100 object-contain">
                                @else
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400"><i class="ik ik-image"></i></span>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('shop.admin.products.edit', $p->id) }}" wire:navigate class="block max-w-md truncate text-left font-medium text-gray-700 hover:text-primary-600">{{ $p->name }}</a>
                                    <span class="font-mono text-xs text-gray-400">{{ $p->sku ?: '—' }}</span>
                                    @if ($p->has_variants)<span class="ml-1 rounded bg-primary-50 px-1.5 text-xs font-medium text-primary-600">{{ $p->variantSummary() }}</span>@endif
                                    @if ($p->tiers->isNotEmpty())<span class="ml-1 text-xs text-green-600">{{ trans_choice(':count bulk price|:count bulk prices', $p->tiers->count(), ['count' => $p->tiers->count()]) }}</span>@endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $p->category?->name ?? '—' }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-gray-700">
                            @if ($p->priceVaries())<span class="text-xs font-normal text-gray-400">{{ __('from') }}</span>@endif
                            {{ $store->money($p->displayPrice()) }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            @if (! $p->track_stock)
                                <span class="text-xs text-gray-400">{{ __('Not tracked') }}</span>
                            @else
                                @php $state = $p->stockState(); @endphp
                                <x-badge color="{{ ['out_of_stock' => 'danger', 'backorder' => 'warning', 'low_stock' => 'warning'][$state] ?? 'success' }}">{{ $p->stock_qty }}</x-badge>
                                @if ($p->has_variants)<span class="block text-xs text-gray-400">{{ __('total') }}</span>@endif
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <x-badge color="{{ ['active' => 'success', 'draft' => 'gray', 'archived' => 'dark'][$p->status] }}">{{ ['active' => __('On sale'), 'draft' => __('Draft'), 'archived' => __('Archived')][$p->status] }}</x-badge>
                            @if ($p->is_featured)<i class="ik ik-star ml-1 text-amber-500" title="{{ __('Featured') }}"></i>@endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-right">
                            <a href="{{ route('shop.admin.products.edit', $p->id) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:underline">{{ __('Edit') }}</a>
                            <a href="{{ route('shop.product', [$store->slug, $p->slug]) }}" target="_blank" class="ml-3 text-sm text-gray-400 hover:text-gray-600" title="{{ __('View in shop') }}"><i class="ik ik-external-link"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10">
                        <x-empty-state title="{{ __('No products found') }}" description="{{ $search || $categoryFilter || $statusFilter || $stockFilter ? __('Try clearing the search or filters.') : __('Add your first product, or import the existing catalogue with php artisan shop:import-botble.') }}" icon="ik ik-package">
                            <x-slot:action><x-button href="{{ route('shop.admin.products.create') }}" wire:navigate><i class="ik ik-plus"></i>{{ __('Add product') }}</x-button></x-slot:action>
                        </x-empty-state>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </x-table>

</div>
