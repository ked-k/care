<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('MAR Chart') }}" subtitle="{{ $serviceUser->name }}" icon="ik ik-clipboard"
                    :breadcrumbs="['Home' => url('dashboard'), 'Medications' => route('medications.manage', $serviceUser), 'MAR Chart' => null]">
        <div class="flex items-center gap-2">
            <x-button variant="outline" size="sm" wire:click="previousWeek"><i class="ik ik-chevron-left"></i></x-button>
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                {{ \Carbon\Carbon::parse($weekStart)->format('d M') }} – {{ \Carbon\Carbon::parse($weekStart)->endOfWeek()->format('d M Y') }}
            </span>
            <x-button variant="outline" size="sm" wire:click="nextWeek"><i class="ik ik-chevron-right"></i></x-button>
            <a href="{{ route('medications.mar-print', ['serviceUser' => $serviceUser->id, 'week' => $weekStart]) }}"
               target="_blank" class="ml-2 text-sm font-medium text-primary-600 hover:underline">
                <i class="ik ik-printer mr-1"></i>{{ __('Print') }}
            </a>
        </div>
    </x-page-header>

    <x-card no-padding hover>
        <x-slot:header>{{ __('Scheduled Medications') }}</x-slot:header>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="sticky left-0 z-10 bg-white px-5 py-3 font-medium dark:bg-gray-900">{{ __('Medication') }}</th>
                        @foreach ($days as $date => $label)
                            <th class="px-2 py-3 font-medium text-center whitespace-nowrap">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($scheduledMeds as $med)
                        <tr wire:key="med-row-{{ $med['id'] }}">
                            <td class="sticky left-0 z-10 bg-white px-5 py-3 dark:bg-gray-900 whitespace-nowrap">
                                <div class="font-semibold text-gray-700 dark:text-gray-200">{{ $med['name'] }}</div>
                                <div class="text-xs text-gray-400">{{ $med['dosage'] }} · {{ ucfirst($med['route']) }} · {{ $med['time'] }}</div>
                            </td>
                            @foreach ($days as $date => $label)
                                @php $cell = $grid[$med['id']][$date] ?? ['state' => 'n/a', 'administration' => null]; @endphp
                                <td class="px-2 py-2 text-center">
                                    @if ($cell['administration'])
                                        <button type="button" wire:click="viewAdministration('{{ $med['id'] }}', '{{ $date }}')"
                                                class="w-full rounded-lg px-2 py-1.5 text-xs font-semibold {{ match($cell['state']) {
                                                    'given' => 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400',
                                                    'prompted' => 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400',
                                                    'refused' => 'bg-accent-50 text-accent-700 dark:bg-accent-500/10 dark:text-accent-400',
                                                    'missed' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                                                    default => 'bg-gray-50 text-gray-500',
                                                } }}">
                                            {{ ucfirst($cell['state']) }}
                                        </button>
                                    @elseif ($cell['state'] === 'n/a')
                                        <span class="text-xs text-gray-300">—</span>
                                    @elseif ($cell['state'] === 'overdue')
                                        <button type="button" wire:click="openRecordForm('{{ $med['id'] }}', '{{ $date }}')"
                                                class="w-full rounded-lg border border-accent-300 bg-accent-50 px-2 py-1.5 text-xs font-semibold text-accent-600 hover:bg-accent-100 dark:border-accent-500/30 dark:bg-accent-500/10">
                                            {{ __('Overdue') }}
                                        </button>
                                    @else
                                        <button type="button" wire:click="openRecordForm('{{ $med['id'] }}', '{{ $date }}')"
                                                class="w-full rounded-lg border border-dashed border-gray-200 px-2 py-1.5 text-xs text-gray-400 hover:border-primary-400 hover:text-primary-500 dark:border-gray-700">
                                            {{ __('Record') }}
                                        </button>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-10">
                            <x-empty-state title="{{ __('No scheduled medications') }}"
                                           description="{{ __('Add a medication with a fixed schedule to see it here.') }}"
                                           icon="ik ik-heart" />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card no-padding hover class="mt-5">
        <x-slot:header>{{ __('PRN (As Needed) Medications') }}</x-slot:header>
        <div class="p-5 space-y-3">
            @forelse ($prnMeds as $med)
                <div class="flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3 dark:border-gray-800" wire:key="prn-{{ $med['id'] }}">
                    <div>
                        <div class="font-semibold text-gray-700 dark:text-gray-200">{{ $med['name'] }}</div>
                        <div class="text-xs text-gray-400">{{ $med['dosage'] }} · {{ ucfirst($med['route']) }}</div>
                    </div>
                    <x-button size="sm" variant="outline" wire:click="openRecordForm('{{ $med['id'] }}')">
                        {{ __('Log dose') }}
                    </x-button>
                </div>
            @empty
                <p class="text-sm text-gray-400">{{ __('No PRN medications for this service user.') }}</p>
            @endforelse
        </div>

        @if (! empty($prnLogsThisWeek))
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                <h6 class="mb-2 text-xs uppercase tracking-wide text-gray-400">{{ __('This week\'s PRN doses') }}</h6>
                <ul class="space-y-1 text-sm">
                    @foreach ($prnLogsThisWeek as $log)
                        <li class="text-gray-600 dark:text-gray-300">
                            {{ $log['actual_time'] ?? $log['scheduled_time'] }} — {{ ucfirst($log['status']) }} ({{ $log['administered_by'] }})
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-card>

    <x-drawer name="mar-record-form" title="{{ __('Record administration') }}" width="w-[28rem]">
        <div class="space-y-4">
            <x-form.select name="recordStatus" label="{{ __('Outcome') }}" wire:model.live="recordStatus" required>
                <option value="given">{{ __('Given') }}</option>
                <option value="prompted">{{ __('Prompted (self-administered with support)') }}</option>
                <option value="refused">{{ __('Refused') }}</option>
                <option value="missed">{{ __('Missed') }}</option>
            </x-form.select>

            @if ($recordStatus !== 'missed')
                <x-form.input type="datetime-local" name="recordActualTime" label="{{ __('Actual time') }}" wire:model="recordActualTime" required />
            @endif

            @if ($recordStatus === 'refused')
                <x-form.textarea name="recordRefusalReason" label="{{ __('Reason for refusal') }}" rows="2" wire:model="recordRefusalReason" required />
            @endif

            <x-form.textarea name="recordNotes" label="{{ __('Notes (optional)') }}" rows="2" wire:model="recordNotes" />
            <x-form.input name="recordWitness" label="{{ __('Witnessed by (optional)') }}" wire:model="recordWitness"
                          placeholder="{{ __('Name of witnessing colleague, if applicable') }}" />

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Photo (optional)') }}</label>
                <input type="file" wire:model="recordPhoto" accept="image/*"
                       class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-primary-600 hover:file:bg-primary-100 dark:file:bg-primary-500/10">
                @if ($recordPhoto)
                    <img src="{{ $recordPhoto->temporaryUrl() }}" class="mt-2 h-20 w-20 rounded-lg object-cover">
                @endif
                @error('recordPhoto') <p class="mt-1 text-xs text-accent-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <x-slot:footer>
            <x-button wire:click="recordAdministration">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-drawer name="mar-view" title="{{ __('Administration record') }}" width="w-[26rem]">
        @if ($viewingAdministration)
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-400">{{ __('Status') }}</span><span class="font-semibold text-gray-700 dark:text-gray-200">{{ ucfirst($viewingAdministration['status']) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">{{ __('Scheduled') }}</span><span>{{ $viewingAdministration['scheduled_time'] }}</span></div>
                @if ($viewingAdministration['actual_time'])
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('Actual') }}</span><span>{{ $viewingAdministration['actual_time'] }}</span></div>
                @endif
                <div class="flex justify-between"><span class="text-gray-400">{{ __('Recorded by') }}</span><span>{{ $viewingAdministration['administered_by'] }}</span></div>
                @if ($viewingAdministration['refusal_reason'])
                    <div><span class="text-gray-400">{{ __('Refusal reason') }}</span><p class="mt-1 text-gray-700 dark:text-gray-200">{{ $viewingAdministration['refusal_reason'] }}</p></div>
                @endif
                @if ($viewingAdministration['notes'])
                    <div><span class="text-gray-400">{{ __('Notes') }}</span><p class="mt-1 text-gray-700 dark:text-gray-200">{{ $viewingAdministration['notes'] }}</p></div>
                @endif
                @if ($viewingAdministration['witness_signature'])
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('Witnessed by') }}</span><span>{{ $viewingAdministration['witness_signature'] }}</span></div>
                @endif
                @if ($viewingAdministration['has_photo'])
                    <img src="{{ $viewingAdministration['photo_url'] }}" class="mt-2 w-full rounded-lg object-cover">
                @endif
            </div>
        @endif
    </x-drawer>
</div>
