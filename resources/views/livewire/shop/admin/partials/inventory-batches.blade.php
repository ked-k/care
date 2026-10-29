{{-- Batch 42: Inventory → Batches & expiry. Needs $lots, $warn, $lot, $trace, $store. --}}
<x-table :paginator="$lots" :exports="false" :print="false">
    <x-slot:filters>
        <div class="relative">
            <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="{{ __('Product, code or lot number') }}"
                   class="w-64 rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500">
        </div>
        <select wire:model.live="lotFilter" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500" aria-label="{{ __('Show') }}">
            <option value="stock">{{ __('Batches in stock') }}</option>
            <option value="soon">{{ __('Expiring within :n days', ['n' => $warn]) }}</option>
            <option value="expired">{{ __('Expired') }}</option>
            <option value="all">{{ __('Every batch, including used up') }}</option>
        </select>
        <select wire:change="setWarningDays($event.target.value)" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500" aria-label="{{ __('Warn before expiry') }}">
            @foreach ([30, 60, 90, 180] as $d)<option value="{{ $d }}" @selected($warn === $d)>{{ __('Warn :n days before expiry', ['n' => $d]) }}</option>@endforeach
        </select>
    </x-slot:filters>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                <th class="px-5 py-3 font-medium">{{ __('Product') }}</th>
                <th class="px-5 py-3 font-medium">{{ __('Lot / batch') }}</th>
                <th class="px-5 py-3 font-medium">{{ __('Expiry') }}</th>
                <th class="px-5 py-3 font-medium">{{ __('Received') }}</th>
                <th class="px-5 py-3 text-right font-medium">{{ __('Left') }}</th>
                <th class="px-5 py-3 text-right font-medium">{{ __('Value') }}</th>
                <th class="px-5 py-3 text-right font-medium">{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse ($lots as $l)
                @php
                    $state = $l->expiryState($warn);
                    $days = $l->daysToExpiry();
                @endphp
                <tr class="hover:bg-gray-50" wire:key="lot-{{ $l->id }}">
                    <td class="px-5 py-3">
                        <span class="block max-w-xs truncate font-medium text-gray-700">{{ $l->itemName() }}</span>
                        <span class="font-mono text-xs text-gray-400">{{ $l->variant?->sku ?: $l->product?->sku ?: '—' }}</span>
                    </td>
                    <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $l->lot_number ?: '—' }}</td>
                    <td class="whitespace-nowrap px-5 py-3">
                        @if ($l->expiry_date)
                            <span class="text-gray-700">{{ $l->expiry_date->format('d/m/Y') }}</span>
                            @if ($state === 'expired')
                                <x-badge color="danger">{{ __('Expired') }}</x-badge>
                            @elseif ($state === 'soon')
                                <x-badge color="warning">{{ trans_choice('{0} today|{1} :count day|[2,*] :count days', $days, ['count' => $days]) }}</x-badge>
                            @endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-5 py-3 text-gray-500">
                        {{ $l->received_on->format('d/m/Y') }}
                        @if ($l->grn)
                            <a href="{{ route('shop.admin.receiving.show', $l->grn_id) }}" wire:navigate class="block text-xs text-primary-600 hover:underline">{{ $l->grn->number }}</a>
                        @else
                            <span class="block text-xs text-gray-400">{{ $l->sourceLabel() }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        <span class="font-semibold text-gray-700">{{ $l->qty_remaining }}</span>
                        <span class="block text-xs text-gray-400">{{ __('of :n', ['n' => $l->qty_received]) }}</span>
                    </td>
                    <td class="whitespace-nowrap px-5 py-3 text-right text-gray-600">{{ $l->unit_cost !== null ? $store->money($l->qty_remaining * (float) $l->unit_cost) : '—' }}</td>
                    <td class="whitespace-nowrap px-5 py-3 text-right">
                        <x-button size="sm" variant="{{ $state === 'expired' && $l->qty_remaining > 0 ? 'danger' : 'ghost' }}" wire:click="openLot('{{ $l->id }}')">
                            {{ $state === 'expired' && $l->qty_remaining > 0 ? __('Write off') : __('Details') }}
                        </x-button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-10">
                    <x-empty-state title="{{ match ($lotFilter) { 'soon' => __('Nothing expiring soon'), 'expired' => __('No expired batches'), default => __('No batches yet') } }}" icon="ik ik-check-circle"
                                   description="{{ __('Batches are created when you receive a delivery. Stock that was already on hand shows as an opening balance.') }}" />
                </td></tr>
            @endforelse
        </tbody>
    </table>
</x-table>

<x-drawer name="lot-detail" title="{{ __('Batch') }}" width="w-[34rem]">
    @if ($lot)
        <div class="space-y-5">
            <div class="rounded-xl bg-gray-50 p-4">
                <p class="font-medium text-gray-700">{{ $lot->itemName() }}</p>
                <p class="text-sm text-gray-600">{{ $lot->label() }}</p>
                <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-gray-400">{{ __('Received') }}</dt><dd class="text-gray-700">{{ $lot->received_on->format('d M Y') }} — {{ $lot->grn?->number ?? $lot->sourceLabel() }}</dd>
                    @if ($lot->grn?->supplier)<dt class="text-gray-400">{{ __('Supplier') }}</dt><dd class="text-gray-700">{{ $lot->grn->supplier->name }}</dd>@endif
                    <dt class="text-gray-400">{{ __('Quantity') }}</dt><dd class="text-gray-700">{{ __(':n left of :m', ['n' => $lot->qty_remaining, 'm' => $lot->qty_received]) }}</dd>
                    <dt class="text-gray-400">{{ __('Unit cost') }}</dt><dd class="text-gray-700">{{ $lot->unit_cost !== null ? $store->money($lot->unit_cost) : '—' }}</dd>
                </dl>
            </div>

            @if ($lot->qty_remaining > 0)
                <form wire:submit="writeOff" id="write-off-form" class="space-y-3 rounded-xl border border-gray-100 p-4">
                    <p class="text-sm font-medium text-gray-700">{{ __('Write off from this batch') }}</p>
                    <div class="grid grid-cols-3 gap-3">
                        <x-form.input type="number" min="1" max="{{ $lot->qty_remaining }}" name="writeOffQty" label="{{ __('Quantity') }}" wire:model="writeOffQty" />
                        <div class="col-span-2"><x-form.input name="writeOffReason" label="{{ __('Reason') }}" wire:model="writeOffReason" placeholder="{{ __('Expired, damaged, recalled…') }}" /></div>
                    </div>
                    <x-button type="submit" size="sm" variant="danger" wire:confirm="{{ __('Take this stock out of the shop?') }}"><i class="ik ik-trash-2"></i>{{ __('Write off') }}</x-button>
                </form>
            @endif

            <div>
                <p class="mb-2 text-sm font-medium text-gray-700">{{ __('Where this batch went') }}</p>
                <p class="mb-3 text-xs text-gray-400">{{ __('Every order and adjustment that took stock from this batch — use it for a supplier recall.') }}</p>
                <ul class="divide-y divide-gray-50 rounded-xl border border-gray-100 text-sm">
                    @forelse ($trace as $a)
                        <li class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="min-w-0">
                                @if ($a->order)
                                    <a href="{{ route('shop.admin.orders.show', $a->order_id) }}" wire:navigate class="font-medium text-primary-600 hover:underline">{{ $a->order->number }}</a>
                                    <span class="text-gray-500">— {{ $a->order->billing_address['name'] ?? $a->order->email }}</span>
                                @else
                                    <span class="text-gray-700">{{ $a->movement?->typeLabel() }}</span>
                                    @if ($a->movement?->note)<span class="block truncate text-xs text-gray-400">{{ $a->movement->note }}</span>@endif
                                @endif
                                <span class="block text-xs text-gray-400">{{ $a->created_at->format('d M Y, H:i') }}@if ($a->movement?->user) — {{ $a->movement->user->name }}@endif</span>
                            </span>
                            <span class="shrink-0 font-semibold {{ $a->qty > 0 ? 'text-accent-600' : 'text-green-600' }}">{{ $a->qty > 0 ? '−'.$a->qty : '+'.abs($a->qty) }}</span>
                        </li>
                    @empty
                        <li class="px-3 py-4 text-center text-gray-400">{{ __('Nothing has moved yet.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif
    <x-slot:footer>
        <x-button variant="ghost" @click="$dispatch('close-drawer', 'lot-detail')">{{ __('Close') }}</x-button>
    </x-slot:footer>
</x-drawer>
