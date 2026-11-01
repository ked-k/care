<div>
    <x-page-header :title="(__('Journals'))" :subtitle="(__('Every entry in the books. Posted entries are never edited — mistakes are reversed.'))" icon="ik ik-book"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Journals' => null]">
        <x-button :href="(route('accounts.journals.create'))" wire:navigate><i class="ik ik-plus"></i>{{ __('Journal entry') }}</x-button>
    </x-page-header>
    <x-card no-padding>
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 lg:flex-row lg:items-center">
            <div class="relative flex-1">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Number, reference or description…') }}"
                       class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
            </div>
            <select wire:model.live="type" class="rounded-lg border border-gray-200 py-2 pl-3 pr-9 text-sm text-gray-600 outline-none focus:border-primary-500">
                <option value="">{{ __('All types') }}</option>
                @foreach (\App\Models\Accounting\Journal::TYPES as $k => $v)<option value="{{ $k }}">{{ __($v) }}</option>@endforeach
            </select>
            <div class="flex items-center gap-2">
                <input type="date" wire:model.live="from" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 outline-none focus:border-primary-500">
                <span class="text-gray-400">–</span>
                <input type="date" wire:model.live="to" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 outline-none focus:border-primary-500">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Number') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Date') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Description') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($journals as $j)
                        <tr class="hover:bg-gray-50" wire:key="j-{{ $j->id }}">
                            <td class="px-5 py-3"><a href="{{ route('accounts.journals.show', $j->id) }}" wire:navigate class="font-mono text-xs font-medium text-gray-700 hover:text-primary-600">{{ $j->number }}</a></td>
                            <td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ $j->date->format('d M Y') }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $j->memo ?: '—' }}@if ($j->reference)<span class="block text-xs text-gray-400">{{ $j->reference }}</span>@endif</td>
                            <td class="px-5 py-3"><x-badge :color="($j->type === 'reversal' ? 'warning' : ($j->isReversed() ? 'dark' : 'gray'))">{{ $j->typeLabel() }}</x-badge>@if ($j->isReversed() && $j->type !== 'reversal')<span class="ml-1 text-xs text-gray-400">{{ __('reversed') }}</span>@endif</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-gray-700">{{ $cur->money($j->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10"><x-empty-state :title="(__('No entries'))" icon="ik ik-book" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($journals->hasPages())<div class="border-t border-gray-100 px-5 py-3">{{ $journals->links('pagination.radmin') }}</div>@endif
    </x-card>
</div>
