<div>
    <x-page-header :title="__('Fixed assets')" :subtitle="__('Equipment, vehicles and property the organisation owns, and their depreciation')" icon="ik ik-box"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Fixed assets' => null]">
        <x-button variant="secondary" :href="route('accounts.reports.show', 'fixed-assets')" wire:navigate><i class="ik ik-file-text"></i>{{ __('Schedule') }}</x-button>
        <x-button variant="secondary" @click="$dispatch('open-modal', 'run-dep')"><i class="ik ik-trending-down"></i>{{ __('Run depreciation') }}</x-button>
        <x-button wire:click="create"><i class="ik ik-plus"></i>{{ __('Add asset') }}</x-button>
    </x-page-header>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card :value="$cur->money($totals['cost'])" :label="__('Cost')" icon="ik ik-box" color="primary" />
        <x-stat-card :value="$cur->money($totals['acc'])" :label="__('Depreciated so far')" icon="ik ik-trending-down" color="amber" />
        <x-stat-card :value="$cur->money($totals['nbv'])" :label="__('Book value now')" icon="ik ik-layers" color="green" />
    </div>

    <div class="mb-3 flex items-center justify-between">
        <p class="text-sm text-gray-500">
            @if ($lastRun){{ __('Last depreciation run: :d (:n)', ['d' => $lastRun->date->format('M Y'), 'n' => $lastRun->number]) }}
                <button type="button" wire:click="undoRun" wire:confirm="{{ __('Reverse the last depreciation run?') }}" class="ml-2 text-gray-400 hover:underline">{{ __('Undo') }}</button>
            @else{{ __('No depreciation has been run yet.') }}@endif
        </p>
        <label class="flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" wire:model.live="disposed" class="rounded border-gray-300 text-primary-600">{{ __('Show disposed') }}</label>
    </div>

    <div class="space-y-6">
        @forelse ($groups as $cat => $list)
            <x-card no-padding :title="\App\Models\Accounting\FixedAsset::CATEGORIES[$cat][0] ?? $cat" wire:key="cat-{{ $cat }}">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-2.5 font-medium">{{ __('Asset') }}</th><th class="px-5 py-2.5 font-medium">{{ __('Bought') }}</th><th class="px-5 py-2.5 font-medium">{{ __('Depreciation') }}</th>
                            <th class="px-5 py-2.5 text-right font-medium">{{ __('Cost') }}</th><th class="px-5 py-2.5 text-right font-medium">{{ __('Depreciated') }}</th><th class="px-5 py-2.5 text-right font-medium">{{ __('Book value') }}</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($list as $a)
                                <tr @class(['hover:bg-gray-50', 'opacity-50' => $a->isDisposed()]) wire:key="fa-{{ $a->id }}">
                                    <td class="px-5 py-3"><a href="{{ route('accounts.assets.show', $a->id) }}" wire:navigate class="font-medium text-gray-700 hover:text-primary-600">{{ $a->name }}</a>
                                        <span class="block text-xs text-gray-400">{{ $a->number }}{{ $a->location ? ' · '.$a->location : '' }}{{ $a->isDisposed() ? ' · '.__('disposed :d', ['d' => $a->disposed_on->format('d M Y')]) : '' }}</span></td>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ $a->purchase_date->format('d M Y') }}</td>
                                    <td class="px-5 py-3 text-gray-500">{{ $a->methodLabel() }}@if ($a->depreciated_to)<span class="block text-xs text-gray-400">{{ __('to :d', ['d' => $a->depreciated_to->format('M Y')]) }}</span>@endif</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right">{{ $cur->money($a->cost) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-gray-500">{{ $cur->money($a->acc) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-gray-700">{{ $a->isDisposed() ? '—' : $cur->money($a->nbv) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @empty
            <x-card><x-empty-state :title="__('No fixed assets yet')" icon="ik ik-box" :description="__('Add laptops, furniture, vehicles and anything else you’ll use for more than a year.')" /></x-card>
        @endforelse
    </div>

    <x-drawer name="asset-form" :title="__('Add fixed asset')" width="w-[34rem]">
        <form wire:submit="save" id="asset-form" class="space-y-4">
            <x-form.input name="form.name" :label="__('What is it?')" wire:model="form.name" required :placeholder="__('e.g. 5 × Dell laptops for the office')" />
            <x-form.select name="form.category" :label="__('Category')" wire:model.live="form.category">
                @foreach (\App\Models\Accounting\FixedAsset::CATEGORIES as $k => $c)<option value="{{ $k }}">{{ __($c[0]) }}</option>@endforeach
            </x-form.select>
            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="form.purchase_date" :label="__('Bought on')" wire:model.live="form.purchase_date" required />
                <x-form.input type="number" step="0.01" name="form.cost" :label="__('Cost')" wire:model="form.cost" required />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-form.select name="form.method" :label="__('Depreciation')" wire:model.live="form.method">
                    @foreach (\App\Models\Accounting\FixedAsset::METHODS as $k => $v)<option value="{{ $k }}">{{ __($v) }}</option>@endforeach
                </x-form.select>
                @if ($form['method'] !== 'none')
                    <x-form.input type="number" step="0.01" name="form.rate" :label="__('% a year')" wire:model="form.rate" />
                @endif
            </div>
            @if ($form['method'] !== 'none')
                <div class="grid grid-cols-2 gap-3">
                    <x-form.input type="date" name="form.depreciation_start" :label="__('Depreciate from')" wire:model="form.depreciation_start" />
                    <x-form.input type="number" step="0.01" name="form.residual_value" :label="__('Worth at the end (optional)')" wire:model="form.residual_value" />
                </div>
            @endif
            <div class="rounded-lg border border-gray-100 p-3">
                <p class="mb-2 text-sm font-medium text-gray-700">{{ __('How is the purchase in the books?') }}</p>
                <div class="space-y-2 text-sm text-gray-600">
                    <label class="flex items-start gap-2"><input type="radio" wire:model.live="form.funding" value="already" class="mt-0.5 text-primary-600">
                        <span>{{ __('Already entered — on a supplier bill or money-out posted to the asset account') }}</span></label>
                    <label class="flex items-start gap-2"><input type="radio" wire:model.live="form.funding" value="bank" class="mt-0.5 text-primary-600">
                        <span>{{ __('Paid from a bank / cash account — post it now') }}</span></label>
                    <label class="flex items-start gap-2"><input type="radio" wire:model.live="form.funding" value="opening" class="mt-0.5 text-primary-600">
                        <span>{{ __('Owned before these books started — add it as an opening balance') }}</span></label>
                </div>
                @if ($form['funding'] === 'bank')
                    <div class="mt-3"><x-form.select name="form.bank_account_id" :label="__('Paid from')" wire:model="form.bank_account_id">
                        @foreach ($banks as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                    </x-form.select></div>
                @elseif ($form['funding'] === 'opening')
                    <div class="mt-3"><x-form.input type="number" step="0.01" name="form.opening_depreciation" :label="__('Depreciation already charged before then')" wire:model="form.opening_depreciation" /></div>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-form.input name="form.serial_number" :label="__('Serial / reg. number')" wire:model="form.serial_number" />
                <x-form.input name="form.location" :label="__('Where / who has it')" wire:model="form.location" />
            </div>
            <x-form.textarea name="form.notes" :label="__('Notes')" wire:model="form.notes" rows="2" />
        </form>
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'asset-form')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="asset-form">{{ __('Add asset') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-modal name="run-dep" :title="__('Run depreciation')" max-width="max-w-2xl">
        <form wire:submit="runDepreciation" class="space-y-4">
            <div class="flex items-end gap-3">
                <div class="flex-1"><x-form.input type="date" name="runTo" :label="__('Charge every month up to the end of')" wire:model.live="runTo" /></div>
            </div>
            <div class="max-h-72 overflow-y-auto rounded-lg border border-gray-100">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($preview as $p)
                            <tr><td class="px-3 py-2 text-gray-700">{{ $p['asset']->number }} · {{ $p['asset']->name }}<span class="block text-xs text-gray-400">{{ trans_choice(':n month|:n months', count($p['months']), ['n' => count($p['months'])]) }}</span></td>
                                <td class="whitespace-nowrap px-3 py-2 text-right font-medium">{{ $cur->money($p['total']) }}</td></tr>
                        @empty
                            <tr><td class="px-3 py-6 text-center text-gray-400">{{ __('Nothing to charge up to that month.') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if ($preview->isNotEmpty())
                        <tfoot><tr class="border-t border-gray-200 font-semibold"><td class="px-3 py-2">{{ __('Total') }}</td><td class="px-3 py-2 text-right">{{ $cur->money($preview->sum('total')) }}</td></tr></tfoot>
                    @endif
                </table>
            </div>
            <div class="flex justify-end gap-2">
                <x-button variant="ghost" @click="$dispatch('close-modal', 'run-dep')">{{ __('Cancel') }}</x-button>
                <x-button type="submit" :disabled="$preview->isEmpty()">{{ __('Post depreciation') }}</x-button>
            </div>
        </form>
    </x-modal>
</div>
