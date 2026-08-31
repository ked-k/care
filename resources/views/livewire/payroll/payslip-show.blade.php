<div>
    <x-page-header title="{{ __('Payslip') }}" subtitle="{{ $payslip->user->name ?? '—' }} · {{ $payslip->payrollRun->reference }}"
                    icon="ik ik-file-text"
                    :breadcrumbs="[
                        'Home' => url('dashboard'),
                        'Payroll' => route('payroll.index'),
                        $payslip->payrollRun->reference => route('payroll.show', $payslip->payrollRun),
                        'Payslip' => null,
                    ]">
        <div class="flex items-center gap-2 print:hidden">
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

    {{--
        Batch 7: the payslip below is styled to read as a standalone printable
        document (letterhead, employee/pay details, earnings & deductions,
        a net-payable callout, sign-off lines, a footer disclaimer) rather
        than an admin-panel card, so it looks right both on screen and when
        printed or saved as a PDF from the browser's print dialog. Everything
        outside #payslip-document (the page header above, the toolbar below,
        the drawer at the bottom) is hidden at print time by the styles at
        the bottom of this file.
    --}}
    <div class="mb-4 flex justify-end gap-2 print:hidden">
        <x-button variant="outline" size="sm" onclick="window.print()">
            {{ __('Print Payslip') }}
        </x-button>
        <x-button variant="outline" size="sm" onclick="window.print()"
                  title="{{ __('Opens the print dialog — choose \'Save as PDF\' as the destination to download it.') }}">
            <i class="ik ik-download mr-1"></i>{{ __('Download PDF') }}
        </x-button>
    </div>

    @php
        $agency = $payslip->payrollRun->agency ?? $payslip->user->agency ?? null;
        $payProfile = $payslip->user->payProfile ?? null;
        $approver = $payslip->payrollRun->approver ?? null;

        $bankAccount = $payProfile?->bank_account_no;
        $bankAccountMasked = $bankAccount ? '— '.str_repeat('•', max(strlen($bankAccount) - 4, 0)).substr($bankAccount, -4) : null;

        $paymentMethodLabel = $payslip->payment_method
            ? ucwords(str_replace('_', ' ', $payslip->payment_method))
            : ($payProfile?->bank_name ? __('Bank Transfer') : null);

        $issueDate = $payslip->paid_at ?? $payslip->created_at;
    @endphp

    <div id="payslip-document" class="mx-auto max-w-3xl overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm print:max-w-none print:rounded-none print:border-0 print:shadow-none">
        <div class="flex items-center justify-between bg-slate-800 px-6 py-2 text-[11px] font-semibold uppercase tracking-wider text-white">
            <span>{{ __('Confidential — Payroll Document') }}</span>
            <span class="font-mono normal-case tracking-normal text-slate-300">{{ __('Ref') }}: {{ $payslip->payrollRun->reference }}</span>
        </div>

        <div class="p-8 text-gray-800">
            {{-- Letterhead --}}
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 pb-6">
                <div class="flex items-start gap-4">
                    @if ($agency?->logo_path)
                        <img src="{{ asset('storage/'.$agency->logo_path) }}" alt="{{ $agency->name }}" class="h-12 w-12 rounded-lg object-contain">
                    @else
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-slate-800 text-lg font-bold text-white">
                            {{ strtoupper(substr($agency?->name ?? 'A', 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h2 class="text-lg font-bold leading-tight text-gray-900">{{ $agency?->name ?? __('Agency') }}</h2>
                        @if ($agency?->address)
                            <p class="text-xs text-gray-500">{{ $agency->address }}</p>
                        @endif
                        @if ($agency?->phone || $agency?->contact_email)
                            <p class="text-xs text-gray-500">
                                @if ($agency?->phone) {{ __('T') }}: {{ $agency->phone }} @endif
                                @if ($agency?->phone && $agency?->contact_email) &nbsp;·&nbsp; @endif
                                @if ($agency?->contact_email) {{ __('E') }}: {{ $agency->contact_email }} @endif
                            </p>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold tracking-widest text-gray-800">{{ __('PAYSLIP') }}</div>
                    <div class="text-sm text-gray-500">{{ $payslip->payrollRun->pay_period_start->format('F Y') }}</div>
                </div>
            </div>

            {{-- Employee / payslip details --}}
            <div class="grid grid-cols-1 gap-6 border-b border-gray-200 py-6 text-sm sm:grid-cols-2">
                <div>
                    <h6 class="mb-3 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Employee Details') }}</h6>
                    <dl class="grid grid-cols-2 gap-y-2">
                        <dt class="text-[11px] uppercase text-gray-400">{{ __('Full Name') }}</dt>
                        <dd class="font-medium text-gray-800">{{ $payslip->user->name ?? '—' }}</dd>

                        <dt class="text-[11px] uppercase text-gray-400">{{ __('Employee ID') }}</dt>
                        <dd class="font-medium text-gray-800">{{ $payProfile?->employee_no ?? '—' }}</dd>

                        <dt class="text-[11px] uppercase text-gray-400">{{ __('Designation') }}</dt>
                        <dd class="font-medium text-gray-800">{{ $payProfile?->job_title ?? '—' }}</dd>
                    </dl>
                </div>
                <div>
                    <h6 class="mb-3 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Payslip Details') }}</h6>
                    <dl class="grid grid-cols-2 gap-y-2">
                        <dt class="text-[11px] uppercase text-gray-400">{{ __('Pay Period') }}</dt>
                        <dd class="font-medium text-gray-800">
                            {{ $payslip->payrollRun->pay_period_start->format('d M Y') }}
                            – {{ $payslip->payrollRun->pay_period_end->format('d M Y') }}
                        </dd>

                        <dt class="text-[11px] uppercase text-gray-400">{{ __('Issue Date') }}</dt>
                        <dd class="font-medium text-gray-800">{{ $issueDate?->format('d M Y') ?? '—' }}</dd>

                        <dt class="text-[11px] uppercase text-gray-400">{{ __('Payment Method') }}</dt>
                        <dd class="font-medium text-gray-800">{{ $paymentMethodLabel ?? '—' }}</dd>

                        @if ($bankAccountMasked)
                            <dt class="text-[11px] uppercase text-gray-400">{{ __('Bank Account') }}</dt>
                            <dd class="font-medium text-gray-800">
                                {{ $payProfile?->bank_name }} {{ $bankAccountMasked }}
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Earnings / deductions --}}
            <div class="grid grid-cols-1 gap-8 py-6 sm:grid-cols-2">
                <div>
                    <div class="mb-2 flex items-center gap-2">
                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-green-100 text-[10px] font-bold text-green-700">+</span>
                        <h6 class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ __('Earnings') }}</h6>
                    </div>
                    <table class="w-full border-t-2 border-green-500 text-sm">
                        <thead>
                            <tr class="text-[11px] uppercase text-gray-400">
                                <th class="py-1 text-left font-normal">{{ __('Description') }}</th>
                                <th class="py-1 text-right font-normal">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="py-2">
                                    {{ __('Regular pay') }}
                                    <span class="block text-xs text-gray-400">{{ number_format($payslip->regular_hours, 2) }} hrs @ {{ number_format($payslip->regular_rate, 2) }}</span>
                                </td>
                                <td class="py-2 text-right">{{ number_format($payslip->regular_hours * $payslip->regular_rate, 2) }}</td>
                            </tr>
                            @if ($payslip->overtime_hours > 0)
                                <tr>
                                    <td class="py-2">
                                        {{ __('Overtime pay') }}
                                        <span class="block text-xs text-gray-400">{{ number_format($payslip->overtime_hours, 2) }} hrs @ {{ number_format($payslip->overtime_rate, 2) }}</span>
                                    </td>
                                    <td class="py-2 text-right">{{ number_format($payslip->overtime_hours * $payslip->overtime_rate, 2) }}</td>
                                </tr>
                            @endif
                            @foreach ($payslip->lines->where('line_type', 'earning') as $line)
                                <tr wire:key="earning-{{ $line->id }}">
                                    <td class="py-2">
                                        {{ ucfirst(str_replace('_', ' ', $line->category)) }}
                                        @if ($line->description)
                                            <span class="block text-xs text-gray-400">{{ $line->description }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-right">
                                        {{ number_format($line->amount, 2) }}
                                        @if ($payslip->status === 'draft')
                                            <button type="button" wire:click="removeLine('{{ $line->id }}')" wire:confirm="{{ __('Remove this line item?') }}"
                                                    class="ml-1 text-gray-300 hover:text-accent-500 print:hidden"><i class="ik ik-x"></i></button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200 font-semibold text-gray-800">
                                <td class="py-2">{{ __('Gross Pay') }}</td>
                                <td class="py-2 text-right">{{ number_format($payslip->gross_pay + $payslip->total_earnings_other, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div>
                    <div class="mb-2 flex items-center gap-2">
                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-red-100 text-[10px] font-bold text-red-700">−</span>
                        <h6 class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ __('Deductions') }}</h6>
                    </div>
                    <table class="w-full border-t-2 border-red-400 text-sm">
                        <thead>
                            <tr class="text-[11px] uppercase text-gray-400">
                                <th class="py-1 text-left font-normal">{{ __('Description') }}</th>
                                <th class="py-1 text-right font-normal">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($payslip->lines->where('line_type', 'deduction') as $line)
                                <tr wire:key="deduction-{{ $line->id }}">
                                    <td class="py-2">
                                        {{ ucfirst(str_replace('_', ' ', $line->category)) }}
                                        @if ($line->description)
                                            <span class="block text-xs text-gray-400">{{ $line->description }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-right">
                                        {{ number_format($line->amount, 2) }}
                                        @if ($payslip->status === 'draft')
                                            <button type="button" wire:click="removeLine('{{ $line->id }}')" wire:confirm="{{ __('Remove this line item?') }}"
                                                    class="ml-1 text-gray-300 hover:text-accent-500 print:hidden"><i class="ik ik-x"></i></button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-2 text-xs text-gray-400">{{ __('No deductions.') }}</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200 font-semibold text-gray-800">
                                <td class="py-2">{{ __('Total Deductions') }}</td>
                                <td class="py-2 text-right">{{ number_format($payslip->total_deductions, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Summary strip --}}
            <div class="grid grid-cols-3 divide-x divide-gray-200 rounded-lg bg-gray-50 py-4 text-center">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-gray-400">{{ __('Gross Pay') }}</div>
                    <div class="text-lg font-bold text-gray-800">{{ number_format($payslip->gross_pay + $payslip->total_earnings_other, 2) }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-gray-400">{{ __('Deductions') }}</div>
                    <div class="text-lg font-bold text-gray-800">{{ number_format($payslip->total_deductions, 2) }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-green-700">{{ __('Net Pay') }}</div>
                    <div class="text-lg font-bold text-green-700">{{ number_format($payslip->net_pay, 2) }}</div>
                </div>
            </div>

            {{-- Net payable callout --}}
            <div class="mt-6 flex justify-end">
                <div class="rounded-lg border border-green-200 bg-green-50 px-6 py-3 text-right">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-green-700">{{ __('Net Payable') }}</div>
                    <div class="text-2xl font-bold text-green-700">{{ number_format($payslip->net_pay, 2) }}</div>
                </div>
            </div>

            {{-- Sign-off --}}
            <div class="mt-10 grid grid-cols-1 gap-8 text-sm sm:grid-cols-2">
                <div class="text-center">
                    @if ($approver?->signature)
                        <img src="{{ asset('storage/'.$approver->signature) }}" alt="" class="mx-auto h-10 object-contain">
                    @endif
                    <div class="mt-1 border-t border-gray-300 pt-1">
                        <div class="text-[11px] uppercase tracking-wide text-gray-400">{{ __('Authorized By') }}</div>
                        <div class="font-medium text-gray-800">{{ $approver?->name ?? '—' }}</div>
                        @if ($payslip->payrollRun->approved_at)
                            <div class="text-xs text-gray-400">{{ $payslip->payrollRun->approved_at->format('d M Y') }}</div>
                        @endif
                    </div>
                </div>
                <div class="text-center">
                    @if ($payslip->user->signature ?? null)
                        <img src="{{ asset('storage/'.$payslip->user->signature) }}" alt="" class="mx-auto h-10 object-contain">
                    @endif
                    <div class="mt-1 border-t border-gray-300 pt-1">
                        <div class="text-[11px] uppercase tracking-wide text-gray-400">{{ __('Received By') }}</div>
                        <div class="font-medium text-gray-800">{{ $payslip->user->name ?? '—' }}</div>
                        @if ($payslip->paid_at)
                            <div class="text-xs text-gray-400">{{ $payslip->paid_at->format('d M Y') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="mt-10 border-t border-gray-100 pt-4 text-center text-[11px] text-gray-400">
                <p>{{ __('This is a computer-generated payslip and does not require a physical signature unless otherwise stated. All figures are shown in your agency\'s standard currency.') }}</p>
                <p class="mt-1">
                    {{ __('Generated') }}: {{ now()->format('d M Y, H:i') }}
                    &nbsp;·&nbsp; {{ __('Page 1 of 1') }}
                    &nbsp;·&nbsp; &copy; {{ now()->year }} {{ $agency?->name ?? config('app.name') }}
                </p>
            </div>
        </div>
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

    {{--
        Print layout: hide everything on the page except the payslip document
        itself. Written this way (rather than targeting the sidebar/topbar by
        class) so it keeps working regardless of which layout wraps this
        component.
    --}}
    <style>
        @media print {
            body * { visibility: hidden; }
            #payslip-document, #payslip-document * { visibility: visible; }
            #payslip-document {
                position: absolute;
                inset: 0;
                width: 100%;
                margin: 0;
                max-width: none;
            }
        }
    </style>
</div>
