<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Timesheet') }}" subtitle="{{ $header['employee_name'] }}" icon="ik ik-clock"
                    :breadcrumbs="['Home' => url('dashboard'), 'Timesheets' => route('timesheets.index'), 'Timesheet' => null]">
        <x-badge color="{{ match($header['status']) {
            'draft' => 'secondary',
            'submitted' => 'primary',
            'approved' => 'success',
            'rejected' => 'danger',
            'paid' => 'green',
            default => 'secondary',
        } }}">
            {{ ucfirst($header['status']) }}
        </x-badge>
    </x-page-header>

    <x-card hover>
        <div class="grid grid-cols-2 gap-4 border-b border-gray-100 pb-4 mb-4 sm:grid-cols-4 dark:border-gray-800">
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wide">{{ __('Employee No.') }}</div>
                <div class="font-semibold text-gray-700 dark:text-gray-200">{{ $header['employee_no'] ?: '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wide">{{ __('Employee') }}</div>
                <div class="font-semibold text-gray-700 dark:text-gray-200">{{ $header['employee_name'] }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wide">{{ __('Manager') }}</div>
                <div class="font-semibold text-gray-700 dark:text-gray-200">{{ $header['manager_name'] ?: '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wide">{{ __('Week Commencing') }}</div>
                <div class="font-semibold text-gray-700 dark:text-gray-200">
                    {{ \Carbon\Carbon::parse($header['week_commencing'])->format('d/m/Y') }}
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-3 py-2 font-medium">{{ __('Day') }}</th>
                        <th class="px-3 py-2 font-medium">{{ __('Date') }}</th>
                        <th class="px-3 py-2 font-medium">{{ __('Day Shift (Start–Finish)') }}</th>
                        <th class="px-3 py-2 font-medium">{{ __('Night Shift (Start–Finish)') }}</th>
                        <th class="px-3 py-2 font-medium">{{ __('Break (mins)') }}</th>
                        <th class="px-3 py-2 font-medium">{{ __('Total Hours') }}</th>
                        <th class="px-3 py-2 font-medium">{{ __('Service User(s)') }}</th>
                        <th class="px-3 py-2 font-medium">{{ __('Initials') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @foreach ($rows as $date => $row)
                        <tr wire:key="row-{{ $date }}">
                            <td class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200">{{ $row['day_label'] }}</td>
                            <td class="px-3 py-2 text-gray-500 whitespace-nowrap dark:text-gray-400">{{ $row['date'] }}</td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-1">
                                    <input type="time" wire:model.lazy="rows.{{ $date }}.day_shift_start" @disabled($readOnly)
                                           class="w-24 rounded-lg border border-gray-200 px-2 py-1 text-xs focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:disabled:bg-gray-800/60">
                                    <span class="text-gray-300">–</span>
                                    <input type="time" wire:model.lazy="rows.{{ $date }}.day_shift_end" @disabled($readOnly)
                                           class="w-24 rounded-lg border border-gray-200 px-2 py-1 text-xs focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:disabled:bg-gray-800/60">
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-1">
                                    <input type="time" wire:model.lazy="rows.{{ $date }}.night_shift_start" @disabled($readOnly)
                                           class="w-24 rounded-lg border border-gray-200 px-2 py-1 text-xs focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:disabled:bg-gray-800/60">
                                    <span class="text-gray-300">–</span>
                                    <input type="time" wire:model.lazy="rows.{{ $date }}.night_shift_end" @disabled($readOnly)
                                           class="w-24 rounded-lg border border-gray-200 px-2 py-1 text-xs focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:disabled:bg-gray-800/60">
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" min="0" wire:model.lazy="rows.{{ $date }}.break_minutes" @disabled($readOnly)
                                       class="w-16 rounded-lg border border-gray-200 px-2 py-1 text-xs text-center focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:disabled:bg-gray-800/60">
                            </td>
                            <td class="px-3 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">
                                {{ number_format($row['total_hours'], 2) }}
                            </td>
                            <td class="px-3 py-2">
                                <select wire:model.lazy="rows.{{ $date }}.service_user_id" @disabled($readOnly)
                                        class="w-36 rounded-lg border border-gray-200 px-2 py-1 text-xs focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:disabled:bg-gray-800/60">
                                    <option value="">—</option>
                                    @foreach ($serviceUserOptions as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" maxlength="10" wire:model.lazy="rows.{{ $date }}.initials" @disabled($readOnly)
                                       class="w-20 rounded-lg border border-gray-200 px-2 py-1 text-xs text-center focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:disabled:bg-gray-800/60">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex flex-col gap-4 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <div class="flex gap-8">
                <div>
                    <div class="text-xs text-gray-400 uppercase tracking-wide">{{ __('Weekly Total Hours') }}</div>
                    <div class="text-xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($weeklyTotalHours, 2) }} {{ __('hrs') }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-400 uppercase tracking-wide">{{ __('Overtime Hours') }}</div>
                    <div class="text-xl font-bold {{ $overtimeHours > 0 ? 'text-amber-600' : 'text-gray-700 dark:text-gray-200' }}">
                        {{ number_format($overtimeHours, 2) }} {{ __('hrs') }}
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @unless ($readOnly)
                    <x-button variant="outline" size="sm" wire:click="save">{{ __('Save draft') }}</x-button>

                    @if (in_array($header['status'], ['draft', 'rejected']))
                        <x-button variant="primary" size="sm" wire:click="submit">{{ __('Submit for approval') }}</x-button>
                    @endif
                @endunless

                @if ($canApprove && $header['status'] === 'submitted')
                    <x-button variant="danger" size="sm" wire:click="reject">{{ __('Reject') }}</x-button>
                    <x-button variant="success" size="sm" wire:click="approve">{{ __('Approve') }}</x-button>
                @endif
            </div>
        </div>
    </x-card>
</div>
