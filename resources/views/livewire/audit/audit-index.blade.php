<div>
    <x-page-header title="{{ __('Audit Log') }}" subtitle="{{ __('Append-only record of significant activity') }}"
        icon="ik ik-list" :breadcrumbs="['Home' => url('dashboard'), 'Audit Log' => null]">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <button type="button" wire:click="switchView('activity')"
                    class="px-3 py-1.5 text-sm {{ $view === 'activity' ? 'bg-primary-500 text-white' : 'bg-white text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                    {{ __('Activity Log') }}
                </button>
                <button type="button" wire:click="switchView('legacy')"
                    class="px-3 py-1.5 text-sm {{ $view === 'legacy' ? 'bg-primary-500 text-white' : 'bg-white text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                    {{ __('Legacy Audit Log') }}
                </button>
            </div>
            <select wire:model.live="userFilter"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <option value="">{{ __('All users') }}</option>
                @foreach ($userOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <input type="text" wire:model.live.debounce.400ms="actionFilter" placeholder="{{ __('Filter by action...') }}"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <input type="date" wire:model.live="fromDate"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <input type="date" wire:model.live="toDate"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <x-button variant="outline" size="sm" wire:click="exportCsv">
                <i class="ik ik-download mr-1"></i>{{ __('Export CSV') }}
            </x-button>
        </div>
    </x-page-header>

    @if ($view === 'legacy')
        <x-card no-padding hover>
            <x-table :paginator="$legacyAudits" title="{{ __('Legacy audit entries (before Batch 20)') }}">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                            <th class="px-5 py-3 font-medium">{{ __('When') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('User') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Action') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Entity') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('IP') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse ($legacyAudits as $audit)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="legacy-audit-{{ $audit->id }}">
                                <td class="px-5 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $audit->created_at->format('d M Y, H:i:s') }}</td>
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-200">{{ $audit->user->name ?? __('System') }}</td>
                                <td class="px-5 py-3"><x-badge color="secondary">{{ $audit->action }}</x-badge></td>
                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $audit->entityLabel() }}</td>
                                <td class="px-5 py-3 text-xs text-gray-400">{{ $audit->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10">
                                    <x-empty-state title="{{ __('No legacy activity recorded') }}" icon="ik ik-list" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-table>
        </x-card>
    @else
        <x-card no-padding hover>
            <x-table :paginator="$activities" title="{{ __('Activity entries') }}">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                            <th class="px-5 py-3 font-medium">{{ __('When') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('User') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Log') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Event') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Description') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Entity') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse ($activities as $activity)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="activity-{{ $activity->id }}">
                                <td class="px-5 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $activity->created_at->format('d M Y, H:i:s') }}</td>
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-200">{{ $activity->causerLabel() }}</td>
                                <td class="px-5 py-3"><x-badge color="secondary">{{ $activity->log_name ?? '—' }}</x-badge></td>
                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $activity->event ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $activity->description }}</td>
                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $activity->entityLabel() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10">
                                    <x-empty-state title="{{ __('No activity recorded yet') }}" icon="ik ik-list" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-table>
        </x-card>
    @endif
</div>
