<div>
    <x-page-header :title="__('Notes to the accounts')" :subtitle="__('The written notes printed with the statements. Figure notes (fixed assets, receivables, payables, cash, reserves) are added from the books automatically.')" icon="ik ik-file-text"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Reports' => route('accounts.reports'), 'Notes' => null]">
        <x-button variant="secondary" :href="route('accounts.audit-pack', $fy->id)" target="_blank"><i class="ik ik-printer"></i>{{ __('Audit pack') }}</x-button>
        <x-button wire:click="save"><i class="ik ik-check"></i>{{ __('Save notes') }}</x-button>
    </x-page-header>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <select wire:model.live="year" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500">
            @foreach ($years as $y)<option value="{{ $y->id }}">{{ $y->name }}</option>@endforeach
        </select>
        <span class="text-sm text-gray-400">{{ __('A new year starts from last year’s wording.') }}</span>
        <button type="button" wire:click="resetTemplate" wire:confirm="{{ __('Replace this year’s notes with the standard wording?') }}" class="ml-auto text-sm text-gray-500 hover:underline">{{ __('Reset to standard wording') }}</button>
    </div>

    <form wire:submit="save" class="space-y-4">
        @foreach ($notes as $i => $n)
            <x-card wire:key="note-{{ $n['id'] }}">
                <div class="flex items-start gap-3">
                    <span class="mt-2 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-500/10 text-sm font-semibold text-primary-600">{{ $i + 1 }}</span>
                    <div class="min-w-0 flex-1 space-y-2">
                        <input type="text" wire:model="notes.{{ $i }}.title" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-800 outline-none focus:border-primary-500">
                        <textarea wire:model="notes.{{ $i }}.body" rows="4" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 outline-none focus:border-primary-500"></textarea>
                    </div>
                    <div class="flex shrink-0 flex-col gap-1 pt-2 text-gray-400">
                        <button type="button" wire:click="move({{ $i }}, -1)" title="{{ __('Move up') }}" class="hover:text-gray-600"><i class="ik ik-chevron-up"></i></button>
                        <button type="button" wire:click="move({{ $i }}, 1)" title="{{ __('Move down') }}" class="hover:text-gray-600"><i class="ik ik-chevron-down"></i></button>
                        <button type="button" wire:click="remove({{ $i }})" wire:confirm="{{ __('Delete this note?') }}" title="{{ __('Delete') }}" class="hover:text-accent-500"><i class="ik ik-trash-2"></i></button>
                    </div>
                </div>
            </x-card>
        @endforeach
        <div class="flex justify-between">
            <x-button variant="secondary" wire:click="add"><i class="ik ik-plus"></i>{{ __('Add a note') }}</x-button>
            <x-button type="submit"><i class="ik ik-check"></i>{{ __('Save notes') }}</x-button>
        </div>
    </form>
</div>
