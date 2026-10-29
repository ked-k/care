<div>
    <x-page-header title="{{ __('Orders') }}" subtitle="{{ __('Everything bought through your storefront') }}" icon="ik ik-shopping-cart"
                   :breadcrumbs="['Shop' => route('shop.admin.dashboard'), 'Orders' => null]">
        <x-button variant="outline" wire:click="exportCsv"><i class="ik ik-download"></i>{{ __('Export CSV') }}</x-button>
    </x-page-header>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['' => __('All'), 'pending' => __('Awaiting payment'), 'processing' => __('Processing'), 'shipped' => __('Dispatched'), 'completed' => __('Completed'), 'cancelled' => __('Cancelled')] as $key => $label)
            <button type="button" wire:click="$set('status', '{{ $key }}')" @class([
                'inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition',
                'border-primary-500 bg-primary-500 text-white' => $status === $key,
                'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' => $status !== $key,
            ])>
                {{ $label }}
                <span @class(['rounded px-1.5 text-xs', 'bg-white/20' => $status === $key, 'bg-gray-100 text-gray-500' => $status !== $key])>{{ $counts[$key] ?? 0 }}</span>
            </button>
        @endforeach
    </div>

    <x-table :paginator="$orders" :exports="false" :print="false">
        <x-slot:filters>
            <div class="relative">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="{{ __('Order number, name, email or postcode') }}"
                       class="w-72 rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500">
            </div>
            <select wire:model.live="payment" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500" aria-label="{{ __('Payment status') }}">
                <option value="">{{ __('Any payment status') }}</option>
                <option value="unpaid">{{ __('Unpaid') }}</option>
                <option value="paid">{{ __('Paid') }}</option>
                <option value="failed">{{ __('Failed') }}</option>
                <option value="refunded">{{ __('Refunded') }}</option>
            </select>
        </x-slot:filters>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">{{ __('Order') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Customer') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Placed') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Delivery') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Payment') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($orders as $o)
                    <tr class="cursor-pointer hover:bg-gray-50" wire:key="o-{{ $o->id }}" onclick="if (!event.target.closest('a')) Livewire.navigate('{{ route('shop.admin.orders.show', $o->id) }}')">
                        <td class="px-5 py-3">
                            <a href="{{ route('shop.admin.orders.show', $o->id) }}" wire:navigate class="font-semibold text-primary-600 hover:underline">{{ $o->number }}</a>
                            <span class="block text-xs text-gray-400">{{ trans_choice(':count line|:count lines', $o->items_count, ['count' => $o->items_count]) }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="block font-medium text-gray-700">{{ $o->billing_address['name'] ?? '—' }}</span>
                            <span class="block text-xs text-gray-400">{{ $o->email }}</span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ $o->created_at->format('d M Y, H:i') }}</td>
                        <td class="px-5 py-3 text-gray-500">
                            {{ $o->delivery_name ?: '—' }}
                            @if ($o->shipping_address)<span class="block text-xs text-gray-400">{{ $o->shipping_address['postcode'] ?? '' }}</span>@endif
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $o->paymentMethodLabel() }}</td>
                        <td class="px-5 py-3">@include('livewire.shop.admin.partials.order-status', ['order' => $o])</td>
                        <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-gray-700">{{ $store->money($o->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10">
                        <x-empty-state title="{{ __('No orders found') }}" description="{{ $search || $status || $payment ? __('Try clearing the search or filters.') : __('Orders appear here as soon as customers check out.') }}" icon="ik ik-shopping-cart" />
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </x-table>
</div>
