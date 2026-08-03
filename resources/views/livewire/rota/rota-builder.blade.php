<div>
    <x-page-header title="{{ __('Rota Builder') }}"
        subtitle="{{ __('Week commencing') }} {{ $period->week_commencing->format('d M Y') }}" icon="ik ik-grid"
        :breadcrumbs="['Home' => url('dashboard'), 'Rota' => route('rota.index'), 'Builder' => null]">
        <div class="flex items-center gap-2">
            <x-badge color="{{ $period->status === 'draft' ? 'secondary' : 'primary' }}">
                {{ ucfirst($period->status) }}
            </x-badge>

            @if ($period->status === 'draft')
                <x-button variant="outline" size="sm" wire:click="publish">{{ __('Publish rota') }}</x-button>
            @endif

            @if ($period->status === 'published')
                <x-button variant="primary" size="sm" wire:click="generateTimesheets">
                    {{ __('Generate timesheets') }}
                </x-button>
            @endif
        </div>
    </x-page-header>

    @if (empty($serviceUsers))
        <x-empty-state title="{{ __('No service users yet') }}"
            description="{{ __('Add a service user before building a rota.') }}" icon="ik ik-users" />
    @else
        <x-card no-padding hover>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr
                            class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                            <th class="sticky left-0 z-10 bg-white px-5 py-3 font-medium dark:bg-gray-900">
                                {{ __('Service User') }}
                            </th>
                            @foreach ($days as $date => $label)
                                <th class="px-3 py-3 font-medium text-center whitespace-nowrap">{{ $label }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @foreach ($serviceUsers as $suId => $suName)
                            <tr wire:key="su-{{ $suId }}">
                                <td
                                    class="sticky left-0 z-10 bg-white px-5 py-3 font-semibold text-gray-700 dark:bg-gray-900 dark:text-gray-200 whitespace-nowrap">
                                    {{ $suName }}
                                </td>
                                @foreach ($days as $date => $label)
                                    @php $cell = $grid[$suId][$date]; @endphp
                                    <td class="px-2 py-2 align-top">
                                        <div class="flex flex-col gap-1 w-36">
                                            {{-- Day shift slot --}}
                                            @if ($cell['day'])
                                                <button type="button"
                                                    wire:click="openEditForm('{{ $cell['day']['id'] }}')"
                                                    @click="$dispatch('open-drawer', 'shift-form')"
                                                    class="rounded-lg border border-primary-200 bg-primary-50 px-2 py-1 text-left text-xs hover:bg-primary-100 dark:border-primary-500/30 dark:bg-primary-500/10">
                                                    <div class="font-semibold text-primary-700 dark:text-primary-300">
                                                        {{ $cell['day']['carer_name'] }}</div>
                                                    <div class="text-primary-500">
                                                        {{ $cell['day']['start'] }}–{{ $cell['day']['end'] }} · Day
                                                    </div>
                                                </button>
                                            @else
                                                <button type="button"
                                                    wire:click="openCreateForm('{{ $suId }}', '{{ $date }}', 'day')"
                                                    @click="$dispatch('open-drawer', 'shift-form')"
                                                    class="rounded-lg border border-dashed border-gray-200 px-2 py-1 text-xs text-gray-400 hover:border-primary-400 hover:text-primary-500 dark:border-gray-700">
                                                    + {{ __('Day') }}
                                                </button>
                                            @endif

                                            {{-- Night shift slot --}}
                                            @if ($cell['night'])
                                                <button type="button"
                                                    wire:click="openEditForm('{{ $cell['night']['id'] }}')"
                                                    @click="$dispatch('open-drawer', 'shift-form')"
                                                    class="rounded-lg border border-accent-200 bg-accent-50 px-2 py-1 text-left text-xs hover:bg-accent-100 dark:border-accent-500/30 dark:bg-accent-500/10">
                                                    <div class="font-semibold text-accent-700 dark:text-accent-300">
                                                        {{ $cell['night']['carer_name'] }}</div>
                                                    <div class="text-accent-500">
                                                        {{ $cell['night']['start'] }}–{{ $cell['night']['end'] }} ·
                                                        Night</div>
                                                </button>
                                            @else
                                                <button type="button"
                                                    wire:click="openCreateForm('{{ $suId }}', '{{ $date }}', 'night')"
                                                    @click="$dispatch('open-drawer', 'shift-form')"
                                                    class="rounded-lg border border-dashed border-gray-200 px-2 py-1 text-xs text-gray-400 hover:border-accent-400 hover:text-accent-500 dark:border-gray-700">
                                                    + {{ __('Night') }}
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif

    <x-drawer name="shift-form" title="{{ $editingShiftId ? __('Edit shift') : __('New shift') }}" width="w-[28rem]">
        <div class="space-y-4">
            <div class="rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800">
                <span class="text-gray-500">{{ __('Service user') }}:</span>
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $formServiceUserName }}</span>
                <span class="mx-1 text-gray-300">·</span>
                <span class="text-gray-500">{{ __('Date') }}:</span>
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $formDate }}</span>
            </div>

            <x-form.select name="formShiftType" label="{{ __('Shift type') }}" wire:model="formShiftType" required>
                <option value="day">{{ __('Day') }}</option>
                <option value="night">{{ __('Night') }}</option>
            </x-form.select>

            <x-form.select name="formCarerId" label="{{ __('Carer') }}" wire:model="formCarerId" required>
                <option value="">{{ __('Select a carer') }}</option>
                @foreach ($carers as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-form.select>

            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="time" name="formStart" label="{{ __('Start') }}" wire:model="formStart"
                    required />
                <x-form.input type="time" name="formEnd" label="{{ __('End') }}" wire:model="formEnd"
                    required />
            </div>

            <x-form.input type="number" min="0" name="formBreakMinutes" label="{{ __('Break (minutes)') }}"
                wire:model="formBreakMinutes" />

            <x-form.textarea name="formNotes" label="{{ __('Notes (optional)') }}" rows="2"
                wire:model="formNotes" />
        </div>

        <x-slot:footer>
            <div class="flex items-center justify-between w-full">
                @if ($editingShiftId)
                    <x-button variant="danger" size="sm" wire:click="deleteShift('{{ $editingShiftId }}')"
                        @click="$dispatch('close-drawer', 'shift-form')">
                        {{ __('Remove shift') }}
                    </x-button>
                @else
                    <span></span>
                @endif
                <x-button wire:click="saveShift">{{ __('Save shift') }}</x-button>
            </div>
        </x-slot:footer>
    </x-drawer>
</div>
