<div>
    <x-page-header title="{{ __('Payroll Run') }} {{ $run->reference }}"
                    subtitle="{{ $run->pay_period_start->format('d M') }} – {{ $run->pay_period_end->format('d M Y') }}"
                    icon="ik ik-dollar-sign"
                    :breadcrumbs="['Home' => url('dashboard'), 'Payroll' => route('payroll.index'), $run->reference => null]">
        <div class="flex items-center gap-2">
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

            @if (in_array($run->status, ['draft', 'processing']))
                <x-button variant="outline" size="sm" wire:click="generatePayslips">{{ __('Generate payslips') }}</x-button>
            @endif

            @if ($run->status === 'processing' && $run->payslips->isNotEmpty())
                <x-button variant="primary" size="sm" wire:click="approveRun">{{ __('Approve run') }}</x-button>
            @endif

            @if ($run->status === 'approved')
                <x-button variant="success" size="sm" wire:click="markPaid">{{ __('Mark as paid') }}</x-button>
            @endif
        </div>
    </x-page-header>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-5">
        <x-stat-card value="{{ number_format($run->total_gross, 2) }}" label="{{ __('Gross Total') }}" icon="ik ik-dollar-sign" color="primary" />
        <x-stat-card value="{{ number_format($run->total_deductions, 2) }}" label="{{ __('Deductions') }}" icon="ik ik-minus-circle" color="amber" />
        <x-stat-card value="{{ number_format($run->total_net, 2) }}" label="{{ __('Net Total') }}" icon="ik ik-check-circle" color="green" />
    </div>

    <x-card no-padding hover>
        <x-slot:header>{{ __('Payslips') }}</x-slot:header>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Carer') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Regular') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Overtime') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Gross') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Deductions') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Net') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($run->payslips as $payslip)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="ps-{{ $payslip->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">{{ $payslip->user->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ number_format($payslip->regular_hours, 2) }} hrs</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ number_format($payslip->overtime_hours, 2) }} hrs</td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ number_format($payslip->gross_pay, 2) }}</td>
                            <td class="px-5 py-3 text-amber-600">{{ number_format($payslip->total_deductions, 2) }}</td>
                            <td class="px-5 py-3 font-medium text-primary-600">{{ number_format($payslip->net_pay, 2) }}</td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ match($payslip->status) {
                                    'draft' => 'secondary',
                                    'approved' => 'success',
                                    'paid' => 'green',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($payslip->status) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('payroll.payslip', $payslip) }}" wire:navigate
                                   class="text-primary-600 hover:underline text-sm font-medium">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10">
                                <x-empty-state title="{{ __('No payslips yet') }}"
                                               description="{{ __('Generate payslips from approved timesheets in this pay period.') }}"
                                               icon="ik ik-dollar-sign" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
