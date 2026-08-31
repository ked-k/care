<div>
    <x-page-header title="{{ __('Audit Log') }}" subtitle="{{ __('Append-only record of significant activity') }}"
        icon="ik ik-list" :breadcrumbs="['Home' => url('dashboard'), 'Audit Log' => null]">
        <div class="flex flex-wrap items-center gap-3">
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
        </div>
    </x-page-header>

    <x-card no-padding hover>
        <x-table :paginator="$audits" title="{{ __('Audit entries') }}">
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
                    @forelse ($audits as $audit)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="audit-{{ $audit->id }}">
                            <td class="px-5 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $audit->created_at->format('d M Y, H:i:s') }}</td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-200">{{ $audit->user->name ?? __('System') }}</td>
                            <td class="px-5 py-3"><x-badge color="secondary">{{ $audit->action }}</x-badge></td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $audit->entityLabel() }}</td>
                            <td class="px-5 py-3 text-gray-400 text-xs">{{ $audit->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10">
                                <x-empty-state title="{{ __('No activity recorded yet') }}" icon="ik ik-list" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>
</div>
