<div>
    <x-page-header title="{{ __('Payroll') }}" subtitle="{{ __('Weekly pay runs generated from approved timesheets') }}"
                    icon="ik ik-dollar-sign" :breadcrumbs="['Home' => url('dashboard'), 'Payroll' => null]">
        <x-button variant="primary" @click="$dispatch('open-drawer', 'new-payroll-run')">
            <i class="ik ik-plus mr-1"></i>{{ __('New payroll run') }}
        </x-button>
    </x-page-header>

    <x-card no-padding hover>
        <x-table :paginator="$runs" title="{{ __('Payroll Runs') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Reference') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Pay Period') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Payslips') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Net Total') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($runs as $run)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="run-{{ $run->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">{{ $run->reference }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                {{ $run->pay_period_start->format('d M') }} – {{ $run->pay_period_end->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $run->payslips_count }}</td>
                            <td class="px-5 py-3 font-medium text-primary-600">{{ number_format($run->total_net, 2) }}</td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ match($run->status) {
                                    'draft' => 'secondary',
                                    'processing' => 'primary',
                                    'approved' => 'success',
                                    'paid' => 'green',
                                    'cancelled' => 'danger',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($run->status) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('payroll.show', $run) }}" wire:navigate
                                   class="text-primary-600 hover:underline text-sm font-medium">{{ __('Open') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-empty-state title="{{ __('No payroll runs yet') }}"
                                               description="{{ __('Create a run once carer timesheets have been approved.') }}"
                                               icon="ik ik-dollar-sign" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>

    <x-drawer name="new-payroll-run" title="{{ __('New payroll run') }}" width="w-[28rem]">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="newPeriodStart" label="{{ __('Period start') }}" wire:model="newPeriodStart" required />
                <x-form.input type="date" name="newPeriodEnd" label="{{ __('Period end') }}" wire:model="newPeriodEnd" required />
            </div>
            <x-form.select name="newFrequency" label="{{ __('Frequency') }}" wire:model="newFrequency" required>
                <option value="weekly">{{ __('Weekly') }}</option>
                <option value="biweekly">{{ __('Biweekly') }}</option>
                <option value="monthly">{{ __('Monthly') }}</option>
            </x-form.select>
        </div>

        <x-slot:footer>
            <x-button wire:click="createRun">{{ __('Create run') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
