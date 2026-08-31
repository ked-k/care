<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Safeguarding') }}" subtitle="{{ __('Concerns, escalations and their outcomes') }}"
        icon="ik ik-shield" :breadcrumbs="['Home' => url('dashboard'), 'Safeguarding' => null]">
        <div class="flex items-center gap-3">
            <select wire:model.live="statusFilter"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <option value="">{{ __('All statuses') }}</option>
                <option value="open">{{ __('Open') }}</option>
                <option value="investigating">{{ __('Investigating') }}</option>
                <option value="resolved">{{ __('Resolved') }}</option>
                <option value="closed">{{ __('Closed') }}</option>
            </select>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search description...') }}"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <x-button variant="primary" size="sm" wire:click="openReportForm"
                @click="$dispatch('open-drawer', 'safeguarding-report-form')">
                <i class="ik ik-plus mr-1"></i>{{ __('Report a concern') }}
            </x-button>
        </div>
    </x-page-header>

    <x-card no-padding hover>
        <x-table :paginator="$reports" title="{{ __('Reports') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Service User') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Reported By') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Reported') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($reports as $report)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="sg-{{ $report->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">
                                {{ $report->serviceUser->name ?? __('Not person-specific') }}
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                {{ ucfirst(str_replace('_', ' ', $report->type)) }}
                            </td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $report->reportedBy->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $report->created_at->format('d M, H:i') }}</td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ $report->statusColor() }}">{{ ucfirst($report->status) }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('safeguarding.show', $report->id) }}" wire:navigate
                                    class="text-primary-600 hover:underline text-sm font-medium">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-empty-state title="{{ __('No safeguarding reports') }}"
                                    description="{{ __('Anyone on staff can report a concern from here.') }}"
                                    icon="ik ik-shield" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>

    <x-drawer name="safeguarding-report-form" title="{{ __('Report a concern') }}" width="w-[30rem]">
        <div class="space-y-4">
            <p class="text-xs text-gray-400">{{ __('Be factual rather than interpretive — record what you saw or heard, when, and who else was present. Assessment is the manager\'s job during investigation.') }}</p>

            <x-form.select name="formServiceUserId" label="{{ __('Service user (optional)') }}" wire:model="formServiceUserId">
                <option value="">{{ __('Not related to a specific person') }}</option>
                @foreach ($this->serviceUserOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-form.select>

            <x-form.select name="formType" label="{{ __('Type of concern') }}" wire:model="formType">
                <option value="safeguarding_concern">{{ __('Safeguarding concern') }}</option>
                <option value="fall">{{ __('Fall') }}</option>
                <option value="medication_incident">{{ __('Medication incident') }}</option>
                <option value="missing_person">{{ __('Missing person') }}</option>
                <option value="injury">{{ __('Injury') }}</option>
                <option value="abuse_concern">{{ __('Abuse concern') }}</option>
                <option value="neglect_concern">{{ __('Neglect concern') }}</option>
                <option value="environmental_hazard">{{ __('Environmental hazard') }}</option>
                <option value="behavioural_incident">{{ __('Behavioural incident') }}</option>
                <option value="staff_conduct">{{ __('Concern about a colleague\'s conduct') }}</option>
                <option value="other">{{ __('Other') }}</option>
            </x-form.select>

            <x-form.textarea name="formDescription" label="{{ __('What happened') }}" rows="5"
                wire:model="formDescription" required
                placeholder="{{ __('What you saw or heard, when, and who else was present...') }}" />

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Photo (optional)') }}</label>
                <input type="file" wire:model="formPhoto" accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-primary-600">
                @error('formPhoto') <span class="text-xs text-accent-500">{{ $message }}</span> @enderror
            </div>
        </div>

        <x-slot:footer>
            <x-button wire:click="submitReport">{{ __('Submit report') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
