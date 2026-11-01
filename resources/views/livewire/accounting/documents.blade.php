<div>
    @php
        $isMoney = $list === 'money';
        $who = $list === 'bill' ? __('Supplier') : ($isMoney ? __('Paid to / from') : __('Customer'));
    @endphp
    <x-page-header :title="($title)" :subtitle="($list === 'invoice' ? __('Invoices you send — care invoices appear here automatically once issued') : ($list === 'bill' ? __('Bills from suppliers — shop deliveries appear here automatically') : __('Spend or receive money straight through a bank or cash account')))"
                   :icon="($list === 'bill' ? 'ik ik-file-minus' : ($isMoney ? 'ik ik-repeat' : 'ik ik-file-text'))"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), $title => null]">
        @if ($isMoney)
            <x-button variant="secondary" :href="(route('accounts.documents.create', 'money_in'))" wire:navigate><i class="ik ik-plus-circle"></i>{{ __('Receive money') }}</x-button>
            <x-button :href="(route('accounts.documents.create', 'money_out'))" wire:navigate><i class="ik ik-shopping-cart"></i>{{ __('Spend money') }}</x-button>
        @else
            <x-button :href="(route('accounts.documents.create', $list))" wire:navigate><i class="ik ik-plus"></i>{{ $list === 'bill' ? __('New bill') : __('New invoice') }}</x-button>
        @endif
    </x-page-header>

    @unless ($isMoney)
        <div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
            <x-stat-card :value="($cur->money($totals['open']))" :label="($list === 'bill' ? __('Still to pay') : __('Still to collect'))" icon="ik ik-clock" color="primary" />
            <x-stat-card :value="($cur->money($totals['overdue']))" :label="(__('Overdue'))" icon="ik ik-alert-circle" color="accent" />
            <x-stat-card :value="($cur->money($totals['month']))" :label="($list === 'bill' ? __('Billed this month') : __('Invoiced this month'))" icon="ik ik-calendar" color="green" />
            <x-stat-card :value="($totals['drafts'])" :label="(__('Drafts'))" icon="ik ik-edit-2" color="amber" />
        </div>
    @endunless

    <x-card no-padding>
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 lg:flex-row lg:items-center">
            <div class="relative flex-1">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Number, reference or name…') }}"
                       class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
            </div>
            <select wire:model.live="status" class="rounded-lg border border-gray-200 py-2 pl-3 pr-9 text-sm text-gray-600 outline-none focus:border-primary-500">
                <option value="">{{ __('All') }}</option>
                @if ($isMoney)
                    <option value="money_in">{{ __('Money in') }}</option>
                    <option value="money_out">{{ __('Money out') }}</option>
                @else
                    <option value="open">{{ __('Unpaid') }}</option>
                    <option value="overdue">{{ __('Overdue') }}</option>
                    <option value="partial">{{ __('Part paid') }}</option>
                    <option value="paid">{{ __('Paid') }}</option>
                    <option value="draft">{{ __('Draft') }}</option>
                @endif
                <option value="void">{{ __('Void') }}</option>
            </select>
            <div class="flex items-center gap-2">
                <input type="date" wire:model.live="from" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 outline-none focus:border-primary-500" title="{{ __('From') }}">
                <span class="text-gray-400">–</span>
                <input type="date" wire:model.live="to" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 outline-none focus:border-primary-500" title="{{ __('To') }}">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Number') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Date') }}</th>
                        <th class="px-5 py-3 font-medium">{{ $who }}</th>
                        <th class="px-5 py-3 font-medium">{{ $isMoney ? __('Account') : __('Due') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Total') }}</th>
                        @unless ($isMoney)<th class="px-5 py-3 text-right font-medium">{{ __('Balance') }}</th>@endunless
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($docs as $d)
                        <tr class="cursor-pointer hover:bg-gray-50" wire:key="doc-{{ $d->id }}" onclick="window.location='{{ route('accounts.documents.show', $d->id) }}'">
                            <td class="px-5 py-3">
                                <a href="{{ route('accounts.documents.show', $d->id) }}" wire:navigate class="font-medium text-gray-700 hover:text-primary-600">{{ $d->number }}</a>
                                @if ($d->source_type)<i class="ik ik-link ml-1 text-xs text-gray-300" title="{{ $d->sourceLabel() }}"></i>@endif
                                @if ($d->reference)<span class="block max-w-xs truncate text-xs text-gray-400">{{ $d->reference }}</span>@endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ $d->issue_date->format('d M Y') }}</td>
                            <td class="px-5 py-3 text-gray-700">
                                {{ $d->contact?->name ?? '—' }}
                                @if ($isMoney)<span class="block text-xs text-gray-400">{{ $d->kindLabel() }}</span>@endif
                            </td>
                            <td @class(['whitespace-nowrap px-5 py-3', 'text-accent-600 font-medium' => $d->isOverdue(), 'text-gray-500' => ! $d->isOverdue()])>
                                {{ $isMoney ? ($d->bankAccount?->name ?? '—') : ($d->due_date?->format('d M Y') ?? '—') }}
                            </td>
                            <td @class(['whitespace-nowrap px-5 py-3 text-right font-semibold', 'text-gray-400 line-through' => $d->status === 'void', 'text-gray-700' => $d->status !== 'void'])>
                                {{ $isMoney && $d->kind === 'money_out' ? '−' : '' }}{{ $cur->money($d->total) }}
                            </td>
                            @unless ($isMoney)
                                <td class="whitespace-nowrap px-5 py-3 text-right text-gray-600">{{ $d->isOpen() ? $cur->money($d->balance()) : '—' }}</td>
                            @endunless
                            <td class="px-5 py-3"><x-badge :color="($d->statusColor())">{{ $d->statusLabel() }}</x-badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10"><x-empty-state :title="(__('Nothing here yet'))" icon="ik ik-file-text" :description="($search || $status ? __('Try clearing the filters.') : '')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($docs->hasPages())
            <div class="border-t border-gray-100 px-5 py-3">{{ $docs->links('pagination.radmin') }}</div>
        @endif
    </x-card>
</div>
