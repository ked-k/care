<div>
    <x-page-header title="{{ __('Rota Builder') }}"
        subtitle="{{ __('Week commencing') }} {{ $period->week_commencing->format('d M Y') }}" icon="ik ik-grid"
        :breadcrumbs="['Home' => url('dashboard'), 'Rota' => route('rota.index'), 'Builder' => null]">
        <div class="flex items-center gap-2">
            <x-badge color="{{ $period->status === 'draft' ? 'secondary' : 'primary' }}">
                {{ ucfirst($period->status) }}
            </x-badge>

            {{-- Batch 18: fills in whatever's missing from this week's active
                 recurring templates (shifts only — CareCall templates are
                 generated from their own management page, not this grid). --}}
            <x-button variant="outline" size="sm" wire:click="generateFromTemplates">
                <i class="ik ik-repeat mr-1"></i>{{ __('Generate from templates') }}
            </x-button>

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
                                    {{-- Batch 18: a live-in placement is scheduled outside this
                                         weekly grid (see "Live-in Placements"), but a manager
                                         building this week's day/night rota still needs to see
                                         at a glance that this person is already covered. --}}
                                    @if (! empty($liveInByServiceUser[$suId]))
                                        @foreach ($liveInByServiceUser[$suId] as $liveIn)
                                            <x-badge color="primary" class="ml-1"
                                                title="{{ __('Live-in placement with :name until :end', ['name' => $liveIn['carer_name'], 'end' => $liveIn['end']]) }}">
                                                <i class="ik ik-home mr-1"></i>{{ __('Live-in') }}
                                            </x-badge>
                                        @endforeach
                                    @endif
                                </td>
                                @foreach ($days as $date => $label)
                                    @php $cell = $grid[$suId][$date]; @endphp
                                    <td class="px-2 py-2 align-top">
                                        <div class="flex flex-col gap-2 w-40">
                                            @foreach (['day' => __('Day'), 'night' => __('Night')] as $slotKey => $slotLabel)
                                                @php
                                                    $slotClasses = $slotKey === 'day'
                                                        ? ['border-primary-200 bg-primary-50 dark:border-primary-500/30 dark:bg-primary-500/10', 'text-primary-700 dark:text-primary-300', 'text-primary-500', 'hover:border-primary-400 hover:text-primary-500']
                                                        : ['border-accent-200 bg-accent-50 dark:border-accent-500/30 dark:bg-accent-500/10', 'text-accent-700 dark:text-accent-300', 'text-accent-500', 'hover:border-accent-400 hover:text-accent-500'];
                                                @endphp
                                                <div class="flex flex-col gap-1">
                                                    @foreach ($cell[$slotKey] as $shiftCard)
                                                        <div
                                                            class="rounded-lg border {{ $slotClasses[0] }} px-2 py-1 text-xs">
                                                            <button type="button"
                                                                wire:click="openEditForm('{{ $shiftCard['id'] }}')"
                                                                @click="$dispatch('open-drawer', 'shift-form')"
                                                                class="block w-full text-left">
                                                                <div class="flex items-center justify-between gap-1">
                                                                    <span class="font-semibold {{ $slotClasses[1] }}">
                                                                        {{ $shiftCard['carer_name'] }}
                                                                    </span>
                                                                    @if ($shiftCard['is_double_handed'])
                                                                        <i class="ik ik-users {{ $slotClasses[2] }}"
                                                                            title="{{ __('Double-handed') }}"></i>
                                                                    @endif
                                                                </div>
                                                                <div class="{{ $slotClasses[2] }}">
                                                                    {{ $shiftCard['start'] }}–{{ $shiftCard['end'] }} ·
                                                                    {{ $slotLabel }}
                                                                </div>
                                                            </button>
                                                            @if ($shiftCard['is_unavailable'])
                                                                <x-badge color="warning" class="mt-1">
                                                                    <i class="ik ik-alert-triangle mr-1"></i>{{ __('Unavailable') }}
                                                                </x-badge>
                                                            @endif
                                                            @if ($shiftCard['is_conflicting'])
                                                                <x-badge color="danger" class="mt-1">
                                                                    <i class="ik ik-alert-triangle mr-1"></i>{{ __('Conflict') }}
                                                                </x-badge>
                                                            @endif
                                                        </div>
                                                        {{-- Batch 11: "take up any carer's session and fill in
                                                             records in case the carer did not" — a manager/admin
                                                             jumps straight into that shift's visit screen from
                                                             here, same screen the carer themself uses. --}}
                                                        <a href="{{ route('rota.visit', $shiftCard['id']) }}" wire:navigate
                                                            class="rounded-lg border border-dashed border-gray-200 px-2 py-1 text-center text-[11px] text-gray-400 hover:border-amber-400 hover:text-amber-600 dark:border-gray-700">
                                                            <i class="ik ik-user-check mr-1"></i>{{ __('Cover shift') }}
                                                        </a>
                                                    @endforeach

                                                    @if (count($cell[$slotKey]) === 0)
                                                        <button type="button"
                                                            wire:click="openCreateForm('{{ $suId }}', '{{ $date }}', '{{ $slotKey }}')"
                                                            @click="$dispatch('open-drawer', 'shift-form')"
                                                            class="rounded-lg border border-dashed border-gray-200 px-2 py-1 text-xs text-gray-400 {{ $slotClasses[3] }} dark:border-gray-700">
                                                            + {{ $slotLabel }}
                                                        </button>
                                                    @elseif (count($cell[$slotKey]) === 1)
                                                        {{-- Batch 18: double-handed — adds a second, independent
                                                             Shift row for the same visit, linked to this one. --}}
                                                        <button type="button"
                                                            wire:click="openAddSecondCarerForm('{{ $cell[$slotKey][0]['id'] }}')"
                                                            @click="$dispatch('open-drawer', 'shift-form')"
                                                            class="rounded-lg border border-dashed border-gray-200 px-2 py-1 text-center text-[11px] text-gray-400 {{ $slotClasses[3] }} dark:border-gray-700">
                                                            <i class="ik ik-user-plus mr-1"></i>{{ __('Add 2nd carer') }}
                                                        </button>
                                                    @endif
                                                </div>
                                            @endforeach
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

    <x-drawer name="shift-form"
        title="{{ $linkingShiftId ? __('Add a second carer') : ($editingShiftId ? __('Edit shift') : __('New shift')) }}"
        width="w-[28rem]">
        <div class="space-y-4">
            <div class="rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-gray-800">
                <span class="text-gray-500">{{ __('Service user') }}:</span>
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $formServiceUserName }}</span>
                <span class="mx-1 text-gray-300">·</span>
                <span class="text-gray-500">{{ __('Date') }}:</span>
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $formDate }}</span>
            </div>

            @if ($linkingShiftId)
                <p class="text-xs text-gray-500">
                    {{ __('This adds a separate shift for the same visit, for a second carer — each carer keeps their own record and their own timesheet hours.') }}
                </p>
            @endif

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
                <x-button wire:click="saveShift">
                    {{ $linkingShiftId ? __('Add carer') : __('Save shift') }}
                </x-button>
            </div>
        </x-slot:footer>
    </x-drawer>
</div>
