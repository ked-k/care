<div>
    <x-page-header title="{{ __('Timesheets') }}" subtitle="{{ __('Weekly hours submitted by carers') }}"
                    icon="ik ik-clock" :breadcrumbs="['Home' => url('dashboard'), 'Timesheets' => null]">
        <select wire:model.live="statusFilter"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <option value="">{{ __('All statuses') }}</option>
            <option value="draft">{{ __('Draft') }}</option>
            <option value="submitted">{{ __('Submitted') }}</option>
            <option value="approved">{{ __('Approved') }}</option>
            <option value="rejected">{{ __('Rejected') }}</option>
            <option value="paid">{{ __('Paid') }}</option>
        </select>
    </x-page-header>

    <x-card no-padding hover>
        <x-table :paginator="$timesheets" title="{{ __('Timesheets') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Carer') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Week Commencing') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Regular') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Overtime') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($timesheets as $timesheet)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="ts-{{ $timesheet->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">
                                {{ $timesheet->user->name ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                {{ $timesheet->week_commencing->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                {{ number_format($timesheet->total_regular_hours, 2) }} hrs
                            </td>
                            <td class="px-5 py-3 {{ $timesheet->total_overtime_hours > 0 ? 'text-amber-600 font-medium' : 'text-gray-400' }}">
                                {{ number_format($timesheet->total_overtime_hours, 2) }} hrs
                            </td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ match($timesheet->status) {
                                    'draft' => 'secondary',
                                    'submitted' => 'primary',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'paid' => 'green',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($timesheet->status) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('timesheets.show', $timesheet) }}" wire:navigate
                                   class="text-primary-600 hover:underline text-sm font-medium">{{ __('Open') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-empty-state title="{{ __('No timesheets yet') }}"
                                               description="{{ __('Publish a rota and generate timesheets to see them here.') }}"
                                               icon="ik ik-clock" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>
</div>
