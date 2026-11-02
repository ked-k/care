<div>
    <x-page-header :title="__('Budgets')" :subtitle="__('What you expect to earn and spend each month — compared with what actually happened in Budget vs actual')" icon="ik ik-target"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Budgets' => null]">
        <x-button variant="secondary" :href="route('accounts.reports.show', ['report' => 'budget-vs-actual', 'year' => $selected->id])" wire:navigate><i class="ik ik-bar-chart-2"></i>{{ __('Budget vs actual') }}</x-button>
        <x-button wire:click="create"><i class="ik ik-plus"></i>{{ __('New budget') }}</x-button>
    </x-page-header>

    <div class="mb-4 flex items-center gap-3">
        <label class="text-sm text-gray-500">{{ __('Financial year') }}</label>
        <select wire:model.live="year" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500">
            @foreach ($years as $y)<option value="{{ $y->id }}">{{ $y->name }}</option>@endforeach
        </select>
        <span class="text-sm text-gray-400">{{ $selected->starts_on->format('j M Y') }} – {{ $selected->ends_on->format('j M Y') }}</span>
    </div>

    <x-card no-padding>
        @if ($budgets->isEmpty())
            <x-empty-state :title="__('No budget for :y yet', ['y' => $selected->name])" icon="ik ik-target"
                           :description="__('Start from last year’s actual figures (plus a percentage), copy another budget, or start blank.')" />
            <div class="pb-6 text-center"><x-button wire:click="create"><i class="ik ik-plus"></i>{{ __('New budget') }}</x-button></div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-2.5 font-medium">{{ __('Budget') }}</th>
                        <th class="px-5 py-2.5 text-right font-medium">{{ $cur->term('income') }}</th>
                        <th class="px-5 py-2.5 text-right font-medium">{{ $cur->term('expenses') }}</th>
                        <th class="px-5 py-2.5 text-right font-medium">{{ $cur->term('result_short') }}</th>
                        <th class="px-5 py-2.5 font-medium"></th></tr></thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($budgets as $b)
                            @php $res = (float) $b->income - (float) $b->spend; @endphp
                            <tr class="hover:bg-gray-50" wire:key="b-{{ $b->id }}">
                                <td class="px-5 py-3">
                                    <a href="{{ route('accounts.budgets.edit', $b->id) }}" wire:navigate class="font-medium text-gray-700 hover:text-primary-600">{{ $b->name }}</a>
                                    @if ($b->is_primary)<x-badge color="primary" class="ml-1">{{ __('Main') }}</x-badge>@endif
                                    <span class="block text-xs text-gray-400">{{ __('Updated :d', ['d' => $b->updated_at->diffForHumans()]) }}{{ $b->creator ? ' · '.$b->creator->name : '' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">{{ $cur->money($b->income) }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">{{ $cur->money($b->spend) }}</td>
                                <td @class(['whitespace-nowrap px-5 py-3 text-right font-semibold', 'text-red-500' => $res < 0, 'text-gray-700' => $res >= 0])>{{ $cur->money($res) }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    <a href="{{ route('accounts.reports.show', ['report' => 'budget-vs-actual', 'year' => $selected->id, 'budget' => $b->id]) }}" wire:navigate class="text-xs text-primary-600 hover:underline">{{ __('Compare') }}</a>
                                    @unless ($b->is_primary)
                                        <button type="button" wire:click="makePrimary('{{ $b->id }}')" class="ml-3 text-xs text-gray-500 hover:underline">{{ __('Make main') }}</button>
                                    @endunless
                                    <button type="button" wire:click="delete('{{ $b->id }}')" wire:confirm="{{ __('Delete :n? This can’t be undone.', ['n' => $b->name]) }}" class="ml-3 text-xs text-red-500 hover:underline">{{ __('Delete') }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
    <p class="mt-3 text-xs text-gray-400">{{ __('The main budget is the one Budget vs actual shows unless you pick another. A year can have several — e.g. “Original” and “Revised”.') }}</p>

    <x-modal name="new-budget" :title="__('New budget')" max-width="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <x-form.select name="form.year" :label="__('Financial year')" wire:model="form.year">
                @foreach ($years as $y)<option value="{{ $y->id }}">{{ $y->name }}</option>@endforeach
            </x-form.select>
            <x-form.input name="form.name" :label="__('Name')" wire:model="form.name" required />
            <div class="space-y-2 rounded-lg border border-gray-100 p-3 text-sm text-gray-600">
                <p class="font-medium text-gray-700">{{ __('Start from') }}</p>
                <label class="flex items-start gap-2"><input type="radio" wire:model.live="form.start" value="actuals" class="mt-0.5 text-primary-600">
                    <span>{{ __('Last year’s actual figures, month by month') }}</span></label>
                <label class="flex items-start gap-2"><input type="radio" wire:model.live="form.start" value="budget" class="mt-0.5 text-primary-600">
                    <span>{{ __('A copy of another budget') }}</span></label>
                <label class="flex items-start gap-2"><input type="radio" wire:model.live="form.start" value="blank" class="mt-0.5 text-primary-600">
                    <span>{{ __('Blank — I’ll type the figures in') }}</span></label>
                @if ($form['start'] === 'budget')
                    <x-form.select name="form.source" :label="__('Copy')" wire:model="form.source">
                        <option value="">{{ __('Choose…') }}</option>
                        @foreach ($allBudgets as $ab)<option value="{{ $ab->id }}">{{ $ab->name }} ({{ $ab->year?->name }})</option>@endforeach
                    </x-form.select>
                @endif
                @if ($form['start'] !== 'blank')
                    <x-form.input type="number" step="0.1" name="form.uplift" :label="__('Adjust every figure by (%)')" wire:model="form.uplift" :hint="__('e.g. 5 for 5% more, −10 for 10% less')" />
                @endif
            </div>
            <div class="flex justify-end gap-2">
                <x-button variant="ghost" @click="$dispatch('close-modal', 'new-budget')">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Create budget') }}</x-button>
            </div>
        </form>
    </x-modal>
</div>
