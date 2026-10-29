<div>
    <x-page-header title="{{ __('Goods received') }}" subtitle="{{ __('Deliveries from suppliers — each one adds its items to stock as batches') }}" icon="ik ik-download"
                   :breadcrumbs="['Shop' => route('shop.admin.dashboard'), 'Goods received' => null]">
        <x-button href="{{ route('shop.admin.receiving.create') }}" wire:navigate><i class="ik ik-plus"></i>{{ __('Receive a delivery') }}</x-button>
    </x-page-header>

    <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-3">
        <x-stat-card variant="soft" value="{{ number_format($monthCount) }}" label="{{ __('Deliveries this month') }}" icon="ik ik-truck" color="primary" />
        <x-stat-card variant="soft" value="{{ $store->money($monthValue) }}" label="{{ __('Received this month (at cost)') }}" icon="ik ik-dollar-sign" color="green" />
        <x-stat-card variant="soft" value="{{ number_format($drafts) }}" label="{{ __('Drafts not yet received') }}" icon="ik ik-edit-2" color="amber" />
    </div>

    <x-table :paginator="$grns" :exports="false" :print="false">
        <x-slot:filters>
            <div class="relative">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="{{ __('GRN, delivery note, product or lot') }}"
                       class="w-72 rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500">
            </div>
            <select wire:model.live="supplier" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500" aria-label="{{ __('Supplier') }}">
                <option value="">{{ __('All suppliers') }}</option>
                @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
            </select>
            <select wire:model.live="status" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500" aria-label="{{ __('Status') }}">
                <option value="">{{ __('Any status') }}</option>
                <option value="draft">{{ __('Draft') }}</option>
                <option value="received">{{ __('Received') }}</option>
                <option value="reversed">{{ __('Reversed') }}</option>
            </select>
        </x-slot:filters>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">{{ __('GRN') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Received') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Supplier') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Delivery note') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Lines') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Units') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Value') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($grns as $g)
                    <tr class="hover:bg-gray-50" wire:key="grn-{{ $g->id }}">
                        <td class="px-5 py-3"><a href="{{ route('shop.admin.receiving.show', $g->id) }}" wire:navigate class="font-semibold text-primary-600 hover:underline">{{ $g->number }}</a></td>
                        <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $g->received_on->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $g->supplier?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $g->supplier_reference ?: '—' }}</td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ $g->lines_count }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-700">{{ number_format((int) $g->lines_sum_qty) }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-right text-gray-700">{{ $store->money($g->total_cost) }}</td>
                        <td class="px-5 py-3"><x-badge color="{{ ['draft' => 'warning', 'received' => 'success', 'reversed' => 'gray'][$g->status] ?? 'gray' }}">{{ $g->statusLabel() }}</x-badge></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-10">
                        <x-empty-state title="{{ __('No deliveries recorded yet') }}" description="{{ __('When stock arrives, record it here: scan or pick each product, enter quantities, lot numbers and expiry dates, then receive it into stock.') }}" icon="ik ik-truck">
                            <x-slot:action><x-button href="{{ route('shop.admin.receiving.create') }}" wire:navigate><i class="ik ik-plus"></i>{{ __('Receive a delivery') }}</x-button></x-slot:action>
                        </x-empty-state>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </x-table>
</div>
