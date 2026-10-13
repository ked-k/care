<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Live-in Placements') }}"
        subtitle="{{ __('Carers placed continuously with a service user, paid a flat daily rate') }}" icon="ik ik-home"
        :breadcrumbs="['Home' => url('dashboard'), 'Rota' => route('rota.index'), 'Live-in Placements' => null]">
        <div class="flex items-center gap-3">
            <input type="text" wire:model.live.debounce.400ms="search"
                placeholder="{{ __('Search by service user or carer...') }}"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <x-button variant="primary" size="sm" wire:click="openCreateForm">
                <i class="ik ik-plus mr-1"></i>{{ __('New placement') }}
            </x-button>
        </div>
    </x-page-header>

    <x-card no-padding hover>
        <x-table :paginator="$placements" title="{{ __('Live-in Placements') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Service User') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Carer') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Start') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('End') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($placements as $placement)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="placement-{{ $placement->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">
                                {{ $placement->serviceUser->name ?? __('Unknown') }}
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                {{ $placement->carer->name ?? __('Unassigned') }}
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                {{ $placement->scheduled_start->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                {{ $placement->scheduled_end->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3">
                                @if ($placement->scheduled_end->isPast())
                                    <x-badge color="secondary">{{ __('Ended') }}</x-badge>
                                @elseif ($placement->scheduled_start->isFuture())
                                    <x-badge color="primary">{{ __('Upcoming') }}</x-badge>
                                @else
                                    <x-badge color="success">{{ __('Active') }}</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right space-x-2 whitespace-nowrap">
                                <button type="button" wire:click="openEditForm('{{ $placement->id }}')"
                                    class="text-primary-600 hover:underline text-sm font-medium">{{ __('Edit') }}</button>
                                @if (! $placement->scheduled_end->isPast())
                                    <button type="button" wire:click="endNow('{{ $placement->id }}')"
                                        wire:confirm="{{ __('End this placement as of today?') }}"
                                        class="text-amber-600 hover:underline text-sm font-medium">{{ __('End now') }}</button>
                                @endif
                                <button type="button" wire:click="deletePlacement('{{ $placement->id }}')"
                                    wire:confirm="{{ __('Remove this placement entirely? This cannot be undone.') }}"
                                    class="text-accent-500 hover:underline text-sm font-medium">{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-empty-state title="{{ __('No live-in placements yet') }}"
                                    description="{{ __('Create one when a carer is placed with a service user continuously rather than on day/night slots.') }}"
                                    icon="ik ik-home" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>

    <x-drawer name="live-in-form" title="{{ $editingShiftId ? __('Edit placement') : __('New live-in placement') }}"
        width="w-[28rem]">
        <div class="space-y-4">
            <x-form.select name="formServiceUserId" label="{{ __('Service user') }}" wire:model="formServiceUserId"
                required>
                <option value="">{{ __('Select a service user') }}</option>
                @foreach ($serviceUsers as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-form.select>

            <x-form.select name="formCarerId" label="{{ __('Carer') }}" wire:model="formCarerId" required>
                <option value="">{{ __('Select a carer') }}</option>
                @foreach ($carers as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-form.select>

            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="formStartDate" label="{{ __('Start date') }}"
                    wire:model="formStartDate" required />
                <x-form.input type="date" name="formEndDate" label="{{ __('End date') }}" wire:model="formEndDate"
                    required />
            </div>

            <p class="text-xs text-gray-500">
                {{ __('Paid at the carer\'s live-in daily rate (set on their staff profile) for every day this placement covers — not hourly day/night pay.') }}
            </p>

            <x-form.textarea name="formNotes" label="{{ __('Notes (optional)') }}" rows="2" wire:model="formNotes" />
        </div>

        <x-slot:footer>
            <x-button wire:click="savePlacement">{{ __('Save placement') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
