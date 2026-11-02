<div>
    <x-page-header :title="$budget->name" :subtitle="__(':y · monthly figures per account', ['y' => $budget->year->name])" icon="ik ik-target"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Budgets' => route('accounts.budgets', ['year' => $budget->financial_year_id]), __('Edit') => null]">
        <x-button variant="secondary" :href="route('accounts.reports.show', ['report' => 'budget-vs-actual', 'year' => $budget->financial_year_id, 'budget' => $budget->id])" wire:navigate class="whitespace-nowrap"><i class="ik ik-bar-chart-2"></i>{{ __('Vs actual') }}</x-button>
        <x-button variant="secondary" @click="$dispatch('open-modal', 'budget-tools')" class="whitespace-nowrap"><i class="ik ik-sliders"></i>{{ __('Fill / adjust') }}</x-button>
        <x-button wire:click="save"><i class="ik ik-save"></i>{{ __('Save') }}</x-button>
    </x-page-header>

    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-form.input name="name" :label="__('Name')" wire:model="name" />
        <div class="md:col-span-2"><x-form.input name="notes" :label="__('Notes (assumptions, who agreed it…)')" wire:model="notes" /></div>
    </div>

    @error('grid.*.*')<x-alert type="danger" class="mb-4">{{ $message }}</x-alert>@enderror

    {{-- Totals and spreading run in the browser; nothing is saved until Save. --}}
    <div x-data="{
            num(v) { v = parseFloat(v); return isNaN(v) ? 0 : v },
            fmt(v) { return v.toLocaleString(undefined, { minimumFractionDigits: {{ (int) $cur->currency_decimals }}, maximumFractionDigits: {{ (int) $cur->currency_decimals }} }) },
            rowTotal(row) { return [...row.querySelectorAll('input[data-m]')].reduce((s, i) => s + this.num(i.value), 0) },
            spread(row, total) {
                const ins = [...row.querySelectorAll('input[data-m]')];
                const d = {{ (int) $cur->currency_decimals }}, f = Math.pow(10, d);
                const each = Math.floor(this.num(total) / 12 * f) / f;
                ins.forEach((i, k) => { i.value = k < 11 ? each.toFixed(d) : (this.num(total) - each * 11).toFixed(d); i.dispatchEvent(new Event('input', { bubbles: true })) });
                this.tick++;
            },
            copyRight(row) {
                const ins = [...row.querySelectorAll('input[data-m]')];
                ins.forEach((i, k) => { if (k) { i.value = ins[0].value; i.dispatchEvent(new Event('input', { bubbles: true })) } });
                this.tick++;
            },
            colTotal(type, m) { return [...this.$root.querySelectorAll('input[data-type=' + type + '][data-m=\'' + m + '\']')].reduce((s, i) => s + this.num(i.value), 0) },
            typeTotal(type) { return [...this.$root.querySelectorAll('input[data-type=' + type + ']')].reduce((s, i) => s + this.num(i.value), 0) },
            tick: 0,
         }" @input="tick++" @budget-refreshed.window="$nextTick(() => tick++)" class="overflow-x-auto rounded-xl border border-gray-100 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="sticky left-0 bg-gray-50 px-3 py-2.5 font-medium" style="min-width:14rem">{{ __('Account') }}</th>
                    @foreach ($months as $m)<th class="px-1 py-2.5 text-right font-medium">{{ $m->format('M') }}</th>@endforeach
                    <th class="px-3 py-2.5 text-right font-medium">{{ __('Year') }}</th>
                    <th class="px-2 py-2.5"></th>
                </tr>
            </thead>
            @foreach (['income' => $cur->term('income'), 'expense' => $cur->term('expenses')] as $type => $label)
                <tbody class="divide-y divide-gray-50">
                    <tr><td colspan="15" class="bg-white px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $label }}</td></tr>
                    @forelse ($sections[$type] ?? [] as $a)
                        @php $act = $actuals[$a->id] ?? null; @endphp
                        <tr class="group hover:bg-gray-50" wire:key="row-{{ $a->id }}">
                            <td class="sticky left-0 bg-white px-3 py-1.5">
                                <span class="text-gray-700">{{ $a->name }}</span>
                                <span class="block text-xs text-gray-400">{{ $a->code }}@if ($act) · {{ __('actual so far :a', ['a' => $cur->money(array_sum($act))]) }}@endif</span>
                            </td>
                            @foreach ($months as $i => $m)
                                <td class="px-0.5 py-1.5">
                                    <input type="number" step="any" min="0" data-m="{{ $i }}" data-type="{{ $type }}" wire:model="grid.{{ $a->id }}.{{ $i }}"
                                           @if ($act) title="{{ __('Actual :m: :a', ['m' => $m->format('M Y'), 'a' => $cur->money($act[$i])]) }}" @endif
                                           class="w-20 rounded border border-gray-200 px-1.5 py-1 text-right text-sm text-gray-700 outline-none focus:border-primary-500">
                                </td>
                            @endforeach
                            <td class="whitespace-nowrap px-3 py-1.5 text-right">
                                <input type="number" step="any" min="0" :value="(tick, rowTotal($el.closest('tr')).toFixed({{ (int) $cur->currency_decimals }}))"
                                       @change.stop="spread($el.closest('tr'), $event.target.value)" @input.stop
                                       title="{{ __('Type a year total to spread it evenly over the months') }}"
                                       class="w-28 rounded border border-gray-200 bg-gray-50 px-1.5 py-1 text-right text-sm font-semibold text-gray-700 outline-none focus:border-primary-500">
                            </td>
                            <td class="whitespace-nowrap px-2 py-1.5 text-right">
                                <button type="button" @click="copyRight($el.closest('tr'))" class="text-gray-400 hover:text-primary-600" title="{{ __('Copy the first month across the year') }}"><i class="ik ik-chevrons-right"></i></button>
                                @unless ($all)
                                    <button type="button" wire:click="removeRow('{{ $a->id }}')" class="ml-1 text-gray-400 hover:text-accent-600" title="{{ __('Remove row') }}"><i class="ik ik-x"></i></button>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="15" class="px-3 py-3 text-sm text-gray-400">{{ __('No accounts budgeted yet — add one below.') }}</td></tr>
                    @endforelse
                    <tr class="border-t border-gray-200 bg-gray-50 font-semibold text-gray-700">
                        <td class="sticky left-0 bg-gray-50 px-3 py-2">{{ __('Total') }} {{ mb_strtolower($label) }}</td>
                        @foreach ($months as $i => $m)<td class="px-1 py-2 text-right text-xs" x-text="(tick, fmt(colTotal('{{ $type }}', {{ $i }})))"></td>@endforeach
                        <td class="px-3 py-2 text-right" x-text="(tick, fmt(typeTotal('{{ $type }}')))"></td><td></td>
                    </tr>
                </tbody>
            @endforeach
            <tfoot>
                <tr class="border-t-2 border-gray-300 font-semibold text-gray-800">
                    <td class="sticky left-0 bg-white px-3 py-2.5">{{ $cur->term('result_short') }}</td>
                    @foreach ($months as $i => $m)<td class="px-1 py-2.5 text-right text-xs" x-text="(tick, fmt(colTotal('income', {{ $i }}) - colTotal('expense', {{ $i }})))"></td>@endforeach
                    <td class="px-3 py-2.5 text-right" x-text="(tick, fmt(typeTotal('income') - typeTotal('expense')))"></td><td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-4">
        @if (! $all && $addable->isNotEmpty())
            <select wire:model.live="addAccount" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500">
                <option value="">{{ __('+ Add an account…') }}</option>
                @foreach ($addable as $type => $group)
                    <optgroup label="{{ $type === 'income' ? $cur->term('income') : $cur->term('expenses') }}">
                        @foreach ($group as $a)<option value="{{ $a->id }}">{{ $a->code }} · {{ $a->name }}</option>@endforeach
                    </optgroup>
                @endforeach
            </select>
        @endif
        <label class="flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" wire:model.live="all" class="rounded border-gray-300 text-primary-600">{{ __('Show every income and expense account') }}</label>
        <span wire:loading class="text-xs text-gray-400">{{ __('Working…') }}</span>
        <span class="ml-auto text-xs text-gray-400">{{ __('Tip: type a figure in the Year column to spread it evenly; hover a month to see what actually happened.') }}</span>
    </div>

    <x-modal name="budget-tools" :title="__('Fill or adjust the budget')" max-width="max-w-md">
        <div class="space-y-4">
            <x-form.input type="number" step="0.1" name="adjust" :label="__('Percentage change')" wire:model="adjust" :hint="__('e.g. 5 for 5% more, −10 for 10% less. Use 0 for no change.')" />
            <div class="space-y-2">
                @if ($hasPrevious)
                    <x-button class="w-full justify-center" variant="secondary" wire:click="fillFromActuals"><i class="ik ik-copy"></i>{{ __('Fill from last year’s actuals (+ the %)') }}</x-button>
                @endif
                <x-button class="w-full justify-center" variant="secondary" wire:click="scale"><i class="ik ik-percent"></i>{{ __('Adjust every figure by the %') }}</x-button>
            </div>
            <p class="text-xs text-gray-400">{{ __('Nothing is saved until you press Save.') }}</p>
        </div>
    </x-modal>
</div>
