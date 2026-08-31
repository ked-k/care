<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Safeguarding Report') }}"
        subtitle="{{ $report->serviceUser->name ?? __('Not person-specific') }}" icon="ik ik-shield"
        :breadcrumbs="['Home' => url('dashboard'), 'Safeguarding' => route('safeguarding.index'), 'Report' => null]">
        <div class="flex items-center gap-2">
            <x-badge color="{{ $report->statusColor() }}">{{ ucfirst($report->status) }}</x-badge>
            @if ($canManage)
                @if (in_array($report->status, ['open', 'investigating']))
                    <x-button variant="outline" size="sm" wire:click="openEscalateForm"
                        @click="$dispatch('open-drawer', 'safeguarding-escalate')">{{ __('Escalate') }}</x-button>
                    <x-button variant="outline" size="sm" wire:click="openInvestigationForm"
                        @click="$dispatch('open-drawer', 'safeguarding-investigate')">{{ __('Add investigation note') }}</x-button>
                    <x-button variant="primary" size="sm" wire:click="openResolveForm"
                        @click="$dispatch('open-drawer', 'safeguarding-resolve')">{{ __('Mark resolved') }}</x-button>
                @elseif ($report->status === 'resolved')
                    <x-button variant="primary" size="sm" wire:click="openCloseForm"
                        @click="$dispatch('open-drawer', 'safeguarding-close')">{{ __('Close') }}</x-button>
                @endif
            @endif
        </div>
    </x-page-header>

    <div class="grid gap-5 lg:grid-cols-3">
        <x-card hover class="lg:col-span-1">
            <x-slot:header>{{ __('Details') }}</x-slot:header>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">{{ __('Type') }}</dt>
                    <dd class="text-gray-700 dark:text-gray-200">{{ ucfirst(str_replace('_', ' ', $report->type)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">{{ __('Reported by') }}</dt>
                    <dd class="text-gray-700 dark:text-gray-200">{{ $report->reportedBy->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">{{ __('Reported at') }}</dt>
                    <dd class="text-gray-700 dark:text-gray-200">{{ $report->created_at->format('d M Y, H:i') }}</dd>
                </div>
                @if ($report->escalatedTo)
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-400">{{ __('Escalated to') }}</dt>
                        <dd class="text-gray-700 dark:text-gray-200">{{ $report->escalatedTo->name }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">{{ __('Description') }}</dt>
                    <dd class="whitespace-pre-line text-gray-700 dark:text-gray-200">{{ $report->description }}</dd>
                </div>
                @if ($report->photo)
                    <div>
                        <dt class="mb-1 text-xs uppercase tracking-wide text-gray-400">{{ __('Photo') }}</dt>
                        <img src="{{ $report->photo->url() }}" class="rounded-lg border border-gray-100" alt="">
                    </div>
                @endif
            </dl>
        </x-card>

        <x-card no-padding hover class="lg:col-span-2">
            <x-slot:header>{{ __('Timeline') }}</x-slot:header>
            <div class="space-y-0 divide-y divide-gray-50 dark:divide-gray-800">
                @forelse ($timeline as $entry)
                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ ucfirst(str_replace('_', ' ', $entry['action'])) }}
                            </span>
                            <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($entry['at'])->format('d M Y, H:i') }}</span>
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            {{ __('by') }} {{ $entry['by_name'] ?? '—' }}
                            @if (! empty($entry['to_name']))
                                &rarr; {{ $entry['to_name'] }}
                            @endif
                        </div>
                        @if (! empty($entry['note']))
                            <p class="mt-2 whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $entry['note'] }}</p>
                        @endif
                    </div>
                @empty
                    <div class="px-5 py-10">
                        <x-empty-state title="{{ __('No activity yet') }}" icon="ik ik-clock" />
                    </div>
                @endforelse
            </div>
        </x-card>
    </div>

    <x-drawer name="safeguarding-escalate" title="{{ __('Escalate report') }}">
        <div class="space-y-4">
            <x-form.select name="escalateToId" label="{{ __('Assign to') }}" wire:model="escalateToId" required>
                <option value="">{{ __('Select a manager') }}</option>
                @foreach ($managerOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-form.select>
            <x-form.textarea name="escalateNote" label="{{ __('Note (optional)') }}" rows="3" wire:model="escalateNote" />
        </div>
        <x-slot:footer>
            <x-button wire:click="escalate">{{ __('Escalate') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-drawer name="safeguarding-investigate" title="{{ __('Add investigation note') }}">
        <x-form.textarea name="investigationNote" label="{{ __('Note') }}" rows="4" wire:model="investigationNote" required />
        <x-slot:footer>
            <x-button wire:click="addInvestigationNote">{{ __('Save note') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-drawer name="safeguarding-resolve" title="{{ __('Mark resolved') }}">
        <x-form.textarea name="resolutionNote" label="{{ __('Resolution note') }}" rows="4" wire:model="resolutionNote" required
            placeholder="{{ __('Describe the outcome and any follow-up.') }}" />
        <x-slot:footer>
            <x-button wire:click="markResolved">{{ __('Mark resolved') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-drawer name="safeguarding-close" title="{{ __('Close report') }}">
        <x-form.textarea name="closeNote" label="{{ __('Note (optional)') }}" rows="3" wire:model="closeNote" />
        <x-slot:footer>
            <x-button wire:click="close">{{ __('Close report') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
