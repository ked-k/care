<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Alerts') }}"
        subtitle="{{ __('Late/missed calls, missing records, medication concerns and urgent flags needing a response') }}"
        icon="ik ik-alert-triangle" :breadcrumbs="['Home' => url('dashboard'), 'Alerts' => null]">
        <div class="flex items-center gap-2">
            <select wire:model.live="typeFilter"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <option value="">{{ __('All types') }}</option>
                <option value="late_call">{{ __('Late call') }}</option>
                <option value="missed_call">{{ __('Missed call') }}</option>
                <option value="missing_record">{{ __('Missing record') }}</option>
                <option value="missing_evidence">{{ __('Missing evidence') }}</option>
                <option value="medication_concern">{{ __('Medication concern') }}</option>
                <option value="urgent_concern">{{ __('Urgent concern') }}</option>
            </select>
            <select wire:model.live="statusFilter"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <option value="open">{{ __('Open & acknowledged') }}</option>
                <option value="acknowledged">{{ __('Acknowledged only') }}</option>
                <option value="resolved">{{ __('Resolved') }}</option>
                <option value="all">{{ __('All') }}</option>
            </select>
            <x-button variant="secondary" size="sm" wire:click="refreshAlerts">
                <i class="ik ik-refresh-cw mr-1"></i>{{ __('Refresh') }}
            </x-button>
        </div>
    </x-page-header>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <x-stat-card value="{{ $stats['open'] }}" label="{{ __('Open') }}" icon="ik ik-alert-triangle" color="amber" />
        <x-stat-card value="{{ $stats['acknowledged'] }}" label="{{ __('Acknowledged') }}" icon="ik ik-eye" color="primary" />
        <x-stat-card value="{{ $stats['resolved'] }}" label="{{ __('Resolved') }}" icon="ik ik-check" color="green" />
    </div>

    <div class="mt-5 space-y-3">
        @forelse ($alerts as $alert)
            <x-card hover wire:key="alert-{{ $alert->id }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <i class="{{ $alert->icon() }} text-gray-400"></i>
                            <h6 class="font-semibold text-gray-700 dark:text-gray-200">{{ $alert->label() }}</h6>
                            <x-badge color="{{ $alert->statusColor() }}">{{ $alert->statusLabel() }}</x-badge>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            @if ($alert->serviceUser)
                                <a href="{{ route('timeline.manage', $alert->service_user_id) }}" wire:navigate
                                    class="font-medium text-primary-600 hover:underline">{{ $alert->serviceUser->name }}</a>
                            @endif
                            <span class="mx-1 text-gray-300">·</span>{{ $alert->created_at->diffForHumans() }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $alert->summary }}</p>
                        @if ($alert->status === 'resolved')
                            <p class="mt-1 text-xs text-gray-400">
                                {{ __('Resolved by :name', ['name' => $alert->resolvedBy->name ?? '—']) }}
                                @if ($alert->resolution_notes)
                                    — {{ $alert->resolution_notes }}
                                @endif
                            </p>
                        @elseif ($alert->status === 'acknowledged')
                            <p class="mt-1 text-xs text-gray-400">
                                {{ __('Acknowledged by :name', ['name' => $alert->acknowledgedBy->name ?? '—']) }}
                            </p>
                        @endif
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-2">
                        <div class="flex items-center gap-1 text-xs text-gray-400">
                            {{ __('Assigned to') }}
                            <select wire:change="reassign('{{ $alert->id }}', $event.target.value)"
                                class="rounded border-gray-200 bg-white px-1.5 py-0.5 text-xs text-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                @foreach ($responderOptions as $id => $name)
                                    <option value="{{ $id }}" @selected($alert->assigned_to == $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            @if ($alert->status === 'open')
                                <x-button size="sm" variant="secondary" wire:click="acknowledge('{{ $alert->id }}')">
                                    {{ __('Acknowledge') }}
                                </x-button>
                            @endif
                            @if ($alert->status !== 'resolved')
                                <x-button size="sm" variant="primary" wire:click="openResolveForm('{{ $alert->id }}')"
                                    @click="$dispatch('open-drawer', 'alert-resolve-form')">
                                    {{ __('Resolve') }}
                                </x-button>
                            @else
                                <x-button size="sm" variant="secondary" wire:click="reopen('{{ $alert->id }}')">
                                    {{ __('Reopen') }}
                                </x-button>
                            @endif
                        </div>
                    </div>
                </div>
            </x-card>
        @empty
            <x-empty-state title="{{ __('No alerts') }}"
                description="{{ __('Nothing matches this filter — try widening it, or check back later.') }}"
                icon="ik ik-alert-triangle" />
        @endforelse
    </div>

    <div class="mt-5">{{ $alerts->links() }}</div>

    <x-drawer name="alert-resolve-form" title="{{ __('Resolve alert') }}" width="w-[28rem]">
        <div class="space-y-4">
            <x-form.textarea name="resolutionNotes" label="{{ __('What was done (optional)') }}" rows="4"
                wire:model="resolutionNotes" placeholder="{{ __('e.g. called the service user, rebooked the visit for 3pm...') }}" />
        </div>

        <x-slot:footer>
            <x-button wire:click="resolve">{{ __('Mark resolved') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
