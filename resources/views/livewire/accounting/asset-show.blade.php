<div>
    <x-page-header :title="$a->name" :subtitle="$a->number.' · '.$a->categoryLabel()" icon="ik ik-box"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Fixed assets' => route('accounts.assets'), $a->number => null]">
        @if ($a->isDisposed())
            <x-badge color="dark" class="px-3 py-1 text-sm">{{ __('Disposed :d', ['d' => $a->disposed_on->format('d M Y')]) }}</x-badge>
            <x-button variant="ghost" wire:click="undoDisposal" wire:confirm="{{ __('Undo the disposal? Its journal is reversed and the asset goes back on the register.') }}"><i class="ik ik-rotate-ccw"></i>{{ __('Undo disposal') }}</x-button>
        @else
            <x-button variant="secondary" @click="$dispatch('open-drawer', 'asset-edit')"><i class="ik ik-edit-2"></i>{{ __('Edit') }}</x-button>
            @if ($a->method !== 'none')<x-button variant="secondary" wire:click="depreciateNow"><i class="ik ik-trending-down"></i>{{ __('Depreciate to last month') }}</x-button>@endif
            <x-button @click="$dispatch('open-modal', 'dispose')"><i class="ik ik-log-out"></i>{{ __('Sell or scrap') }}</x-button>
        @endif
    </x-page-header>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card :value="$cur->money($a->cost)" :label="__('Cost')" icon="ik ik-box" color="primary" />
        <x-stat-card :value="$cur->money($acc)" :label="__('Depreciated so far')" icon="ik ik-trending-down" color="amber" />
        <x-stat-card :value="$cur->money($a->isDisposed() ? 0 : $nbv)" :label="__('Book value')" icon="ik ik-layers" color="green" />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-card no-padding :title="__('Depreciation charged')">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400"><th class="px-5 py-2.5 font-medium">{{ __('Month') }}</th><th class="px-5 py-2.5 font-medium">{{ __('Journal') }}</th><th class="px-5 py-2.5 text-right font-medium">{{ __('Charge') }}</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            @if ((float) $a->opening_depreciation > 0)
                                <tr><td class="px-5 py-2 text-gray-500">{{ __('Before these books') }}</td><td class="px-5 py-2"></td><td class="px-5 py-2 text-right">{{ $cur->money($a->opening_depreciation) }}</td></tr>
                            @endif
                            @forelse ($a->entries->sortByDesc('period_end') as $e)
                                <tr><td class="px-5 py-2 text-gray-700">{{ $e->period_end->format('M Y') }}</td>
                                    <td class="px-5 py-2"><a href="{{ route('accounts.journals.show', $e->journal_id) }}" wire:navigate class="text-xs text-primary-600 hover:underline">{{ $e->journal?->number }}</a></td>
                                    <td class="px-5 py-2 text-right">{{ $cur->money($e->amount) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-6 text-center text-gray-400">{{ __('Nothing charged yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
        <div class="space-y-6">
            <x-card :title="__('Details')">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-gray-400">{{ __('Bought') }}</dt><dd class="text-gray-700">{{ $a->purchase_date->format('d M Y') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-400">{{ __('Depreciation') }}</dt><dd class="text-right text-gray-700">{{ $a->methodLabel() }}</dd></div>
                    @if ($a->method !== 'none')<div class="flex justify-between gap-3"><dt class="text-gray-400">{{ __('From') }}</dt><dd class="text-gray-700">{{ $a->depreciation_start->format('M Y') }}</dd></div>@endif
                    @if ((float) $a->residual_value)<div class="flex justify-between gap-3"><dt class="text-gray-400">{{ __('Worth at the end') }}</dt><dd class="text-gray-700">{{ $cur->money($a->residual_value) }}</dd></div>@endif
                    @if ($a->serial_number)<div class="flex justify-between gap-3"><dt class="text-gray-400">{{ __('Serial / reg.') }}</dt><dd class="text-gray-700">{{ $a->serial_number }}</dd></div>@endif
                    @if ($a->location)<div class="flex justify-between gap-3"><dt class="text-gray-400">{{ __('Where') }}</dt><dd class="text-gray-700">{{ $a->location }}</dd></div>@endif
                    <div class="flex justify-between gap-3"><dt class="text-gray-400">{{ __('Accounts') }}</dt><dd class="text-right text-xs text-gray-500">{{ $a->assetAccount->label() }}<br>{{ $a->accumulatedAccount->label() }}</dd></div>
                    @if ($a->isDisposed())<div class="flex justify-between gap-3"><dt class="text-gray-400">{{ __('Sold for') }}</dt><dd class="text-gray-700">{{ $cur->money($a->disposal_proceeds) }}</dd></div>@endif
                    @if ($a->notes)<div><dt class="text-gray-400">{{ __('Notes') }}</dt><dd class="whitespace-pre-line text-gray-600">{{ $a->notes }}</dd></div>@endif
                </dl>
            </x-card>
            @if ($byYear->isNotEmpty())
                <x-card no-padding :title="__('By financial year')">
                    <div class="divide-y divide-gray-50 text-sm">
                        @foreach ($byYear as $y => $amt)<div class="flex justify-between px-5 py-2"><span class="text-gray-600">{{ $y }}</span><span class="font-medium">{{ $cur->money($amt) }}</span></div>@endforeach
                    </div>
                </x-card>
            @endif
            @if ($projection)
                <x-card no-padding :title="__('Next five years')" :subtitle="__('If nothing changes')">
                    <div class="divide-y divide-gray-50 text-sm">
                        @foreach ($projection as $p)<div class="flex justify-between px-5 py-2"><span class="text-gray-600">{{ __('to :d', ['d' => $p['to']]) }}</span><span>{{ $cur->money($p['charge']) }} <span class="text-xs text-gray-400">→ {{ $cur->money($p['nbv']) }}</span></span></div>@endforeach
                    </div>
                </x-card>
            @endif
        </div>
    </div>

    <x-drawer name="asset-edit" :title="__('Edit asset')">
        <form wire:submit="saveDetails" id="asset-edit-form" class="space-y-4">
            <x-form.input name="edit.name" :label="__('Name')" wire:model="edit.name" required />
            <x-form.input name="edit.serial_number" :label="__('Serial / reg. number')" wire:model="edit.serial_number" />
            <x-form.input name="edit.location" :label="__('Where / who has it')" wire:model="edit.location" />
            <x-form.textarea name="edit.notes" :label="__('Notes')" wire:model="edit.notes" rows="3" />
            <p class="text-xs text-gray-400">{{ __('Cost and depreciation can’t be changed once posted — sell or scrap it and add it again if they were wrong.') }}</p>
        </form>
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'asset-edit')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="asset-edit-form">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-modal name="dispose" :title="__('Sell or scrap :n', ['n' => $a->name])">
        <form wire:submit="disposeAsset" class="space-y-4">
            <p class="text-sm text-gray-600">{{ __('Depreciation is charged up to the end of the month before, then the asset leaves the register. Any difference between the sale price and the book value is posted as a profit or loss.') }}</p>
            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="dispose.date" :label="__('Date')" wire:model="dispose.date" required />
                <x-form.input type="number" step="0.01" name="dispose.proceeds" :label="__('Sold for (0 if scrapped)')" wire:model="dispose.proceeds" />
            </div>
            <x-form.select name="dispose.bank_account_id" :label="__('Money went into')" wire:model="dispose.bank_account_id">
                @foreach ($banks as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
            </x-form.select>
            <div class="flex justify-end gap-2">
                <x-button variant="ghost" @click="$dispatch('close-modal', 'dispose')">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Post disposal') }}</x-button>
            </div>
        </form>
    </x-modal>
</div>
