<div>
    <x-page-header :title="(__('Chart of accounts'))" :subtitle="(__('The headings everything is posted to. Headings decide where an account appears on the statements.'))" icon="ik ik-list"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Chart of accounts' => null]">
        <x-button wire:click="create"><i class="ik ik-plus"></i>{{ __('Add account') }}</x-button>
    </x-page-header>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative max-w-md flex-1">
            <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Code or name…') }}"
                   class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" wire:model.live="archived" class="rounded border-gray-300 text-primary-600">{{ __('Show archived') }}</label>
    </div>

    <div class="space-y-6">
        @foreach ($groups as $type => $g)
            <x-card no-padding wire:key="grp-{{ $type }}">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <h3 class="font-semibold text-gray-700">{{ __($g['label']) }} <span class="text-sm font-normal text-gray-400">({{ $g['accounts']->count() }})</span></h3>
                    <button type="button" wire:click="create('{{ $type }}')" class="text-sm font-medium text-primary-600 hover:underline"><i class="ik ik-plus"></i> {{ __('Add') }}</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($g['accounts'] as $a)
                                <tr @class(['hover:bg-gray-50', 'opacity-50' => ! $a->is_active]) wire:key="acc-{{ $a->id }}">
                                    <td class="w-20 px-5 py-2.5 font-mono text-xs text-gray-500">{{ $a->code }}</td>
                                    <td class="px-5 py-2.5">
                                        <button type="button" wire:click="edit('{{ $a->id }}')" class="text-left font-medium text-gray-700 hover:text-primary-600">{{ $a->name }}</button>
                                        @if ($a->system_key)<i class="ik ik-zap ml-1 text-xs text-amber-500" title="{{ __('Used by automatic posting: :w', ['w' => __(\App\Services\Accounting\DefaultChart::SYSTEM_LABELS[$a->system_key] ?? $a->system_key)]) }}"></i>@endif
                                        @unless ($a->is_active)<span class="ml-1 text-xs text-gray-400">({{ __('archived') }})</span>@endunless
                                    </td>
                                    <td class="px-5 py-2.5 text-gray-400">{{ $a->subtypeLabel() }}</td>
                                    <td class="whitespace-nowrap px-5 py-2.5 text-right">
                                        @if (abs($a->balance) >= 0.005)
                                            <a href="{{ route('accounts.reports.show', ['report' => 'ledger', 'account' => $a->id]) }}" wire:navigate @class(['font-medium hover:underline', 'text-accent-600' => $a->balance < 0, 'text-gray-700' => $a->balance >= 0])>{{ $cur->money($a->balance) }}</a>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endforeach
    </div>

    <x-drawer name="account-form" :title="($editingId ? __('Edit account') : __('Add account'))" width="w-[28rem]">
        <form wire:submit="save" id="account-form" class="space-y-4">
            <div class="grid grid-cols-3 gap-3">
                <x-form.input name="form.code" :label="(__('Code'))" wire:model="form.code" required />
                <div class="col-span-2"><x-form.input name="form.name" :label="(__('Name'))" wire:model="form.name" required /></div>
            </div>
            <x-form.select name="form.type" :label="(__('Class'))" wire:model.live="form.type">
                @foreach (\App\Models\Accounting\Account::TYPES as $k => $v)<option value="{{ $k }}">{{ __($v) }}</option>@endforeach
            </x-form.select>
            <x-form.select name="form.subtype" :label="(__('Heading on the statements'))" wire:model="form.subtype">
                @foreach ($subtypes as $k => [$t, $label])<option value="{{ $k }}">{{ __($label) }}</option>@endforeach
            </x-form.select>
            <x-form.textarea name="form.description" :label="(__('What goes here'))" wire:model="form.description" rows="2" />
            <x-form.checkbox name="form.is_active" :label="(__('In use (untick to archive)'))" wire:model="form.is_active" />
            @error('form.is_active')<p class="text-xs text-accent-600">{{ $message }}</p>@enderror
            @if ($editing?->system_key)
                <p class="rounded-lg bg-amber-500/10 px-3 py-2 text-xs text-amber-700"><i class="ik ik-zap"></i> {{ __('Automatic posting uses this account for: :w.', ['w' => __(\App\Services\Accounting\DefaultChart::SYSTEM_LABELS[$editing->system_key] ?? $editing->system_key)]) }}</p>
            @endif
        </form>
        <x-slot:footer>
            @if ($editingId && ! $editing?->system_key)
                <x-button variant="ghost" class="mr-auto" wire:click="delete" wire:confirm="{{ __('Delete this account?') }}"><i class="ik ik-trash-2"></i>{{ __('Delete') }}</x-button>
            @endif
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'account-form')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="account-form">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
