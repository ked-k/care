<div>
    <x-page-header title="{{ __('Payslip') }}" subtitle="{{ $payslip->user->name ?? '—' }} · {{ $payslip->payrollRun->reference }}"
                    icon="ik ik-file-text"
                    :breadcrumbs="[
                        'Home' => url('dashboard'),
                        'Payroll' => route('payroll.index'),
                        $payslip->payrollRun->reference => route('payroll.show', $payslip->payrollRun),
                        'Payslip' => null,
                    ]">
        <div class="flex items-center gap-2">
            <x-badge color="{{ match($payslip->status) {
                'draft' => 'secondary',
                'approved' => 'success',
                'paid' => 'green',
                default => 'secondary',
            } }}">
                {{ ucfirst($payslip->status) }}
            </x-badge>

            @if ($payslip->status === 'draft')
                <x-button variant="outline" size="sm" @click="$dispatch('open-drawer', 'payslip-line-form')">
                    {{ __('Add line item') }}
                </x-button>
                <x-button variant="primary" size="sm" wire:click="approve">{{ __('Approve') }}</x-button>
            @endif
        </div>
    </x-page-header>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <x-card class="lg:col-span-2" hover>
            <x-slot:header>{{ __('Hours & Pay') }}</x-slot:header>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    <tr>
                        <td class="py-2 text-gray-500">{{ __('Regular hours') }}</td>
                        <td class="py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($payslip->regular_hours, 2) }} hrs @ {{ number_format($payslip->regular_rate, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-500">{{ __('Overtime hours') }}</td>
                        <td class="py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($payslip->overtime_hours, 2) }} hrs @ {{ number_format($payslip->overtime_rate, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 font-semibold text-gray-700 dark:text-gray-200">{{ __('Gross pay') }}</td>
                        <td class="py-2 text-right font-semibold text-gray-700 dark:text-gray-200">{{ number_format($payslip->gross_pay, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-6">
                <h6 class="mb-2 text-xs uppercase tracking-wide text-gray-400">{{ __('Earnings & Deductions') }}</h6>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse ($payslip->lines as $line)
                            <tr wire:key="line-{{ $line->id }}">
                                <td class="py-2">
                                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ ucfirst(str_replace('_', ' ', $line->category)) }}</span>
                                    @if ($line->description)
                                        <span class="text-gray-400 text-xs"> — {{ $line->description }}</span>
                                    @endif
                                </td>
                                <td class="py-2 text-right {{ $line->line_type === 'deduction' ? 'text-amber-600' : 'text-green-600' }}">
                                    {{ $line->line_type === 'deduction' ? '−' : '+' }}{{ number_format($line->amount, 2) }}
                                </td>
                                @if ($payslip->status === 'draft')
                                    <td class="py-2 pl-2 text-right">
                                        <button type="button" wire:click="removeLine('{{ $line->id }}')" wire:confirm="{{ __('Remove this line item?') }}"
                                                class="text-gray-300 hover:text-accent-500"><i class="ik ik-x"></i></button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-gray-400 text-sm">{{ __('No earnings or deductions added yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card hover>
            <x-slot:header>{{ __('Summary') }}</x-slot:header>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">{{ __('Gross pay') }}</span><span class="text-gray-700 dark:text-gray-300">{{ number_format($payslip->gross_pay, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('Other earnings') }}</span><span class="text-green-600">+{{ number_format($payslip->total_earnings_other, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">{{ __('Deductions') }}</span><span class="text-amber-600">−{{ number_format($payslip->total_deductions, 2) }}</span></div>
                <div class="flex justify-between border-t border-gray-100 pt-3 font-bold text-gray-800 dark:border-gray-800 dark:text-gray-100">
                    <span>{{ __('Net pay') }}</span><span>{{ number_format($payslip->net_pay, 2) }}</span>
                </div>
            </div>
        </x-card>
    </div>

    <x-drawer name="payslip-line-form" title="{{ __('Add line item') }}" width="w-[26rem]">
        <div class="space-y-4">
            <x-form.select name="lineType" label="{{ __('Type') }}" wire:model.live="lineType" required>
                <option value="deduction">{{ __('Deduction') }}</option>
                <option value="earning">{{ __('Earning') }}</option>
            </x-form.select>

            <x-form.select name="category" label="{{ __('Category') }}" wire:model="category" required>
                <option value="">{{ __('Select a category') }}</option>
                @foreach ($this->categoryOptions() as $option)
                    <option value="{{ $option }}">{{ ucfirst(str_replace('_', ' ', $option)) }}</option>
                @endforeach
            </x-form.select>

            <x-form.input type="number" step="0.01" min="0" name="amount" label="{{ __('Amount') }}" wire:model="amount" required />

            <x-form.textarea name="description" label="{{ __('Note (optional)') }}" rows="2" wire:model="description" />
        </div>

        <x-slot:footer>
            <x-button wire:click="addLine">{{ __('Add line') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
