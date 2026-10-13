<div>
    <x-page-header title="{{ __('Invoice') }}" subtitle="{{ $invoice->serviceUser->name ?? '—' }} · {{ $invoice->invoiceRun->reference }}"
                    icon="ik ik-file-text"
                    :breadcrumbs="[
                        'Home' => url('dashboard'),
                        'Invoicing' => route('invoicing.index'),
                        $invoice->invoiceRun->reference => route('invoicing.show', $invoice->invoiceRun),
                        'Invoice' => null,
                    ]">
        <div class="flex items-center gap-2 print:hidden">
            <x-badge color="{{ match($invoice->status) {
                'draft' => 'secondary',
                'issued' => 'primary',
                'paid' => 'green',
                default => 'secondary',
            } }}">
                {{ ucfirst($invoice->status) }}
            </x-badge>

            @if ($invoice->status === 'draft' && $this->canManage())
                <x-button variant="outline" size="sm" @click="$dispatch('open-drawer', 'invoice-adjustment-form')">
                    {{ __('Add adjustment') }}
                </x-button>
                <x-button variant="primary" size="sm" wire:click="issue">{{ __('Issue invoice') }}</x-button>
            @endif

            @if ($invoice->status === 'issued' && $this->canManage())
                <x-button variant="success" size="sm" @click="$dispatch('open-drawer', 'invoice-paid-form')">{{ __('Mark as paid') }}</x-button>
            @endif
        </div>
    </x-page-header>

    {{--
        Mirrors the Batch 7 payslip printable-document treatment: a
        letterhead-style document (#invoice-document) that reads right on
        screen and when printed/saved as a PDF from the browser's print
        dialog. Everything outside it is hidden at print time.
    --}}
    <div class="mb-4 flex justify-end gap-2 print:hidden">
        <x-button variant="outline" size="sm" onclick="window.print()">
            {{ __('Print Invoice') }}
        </x-button>
        <x-button variant="outline" size="sm" onclick="window.print()"
                  title="{{ __('Opens the print dialog — choose \'Save as PDF\' as the destination to download it.') }}">
            <i class="ik ik-download mr-1"></i>{{ __('Download PDF') }}
        </x-button>
    </div>

    @php
        $agency = $invoice->invoiceRun->agency ?? $invoice->serviceUser->agency ?? null;
        $counterpart = $invoice->splitCounterpart();
    @endphp

    <div id="invoice-document" class="mx-auto max-w-3xl overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm print:max-w-none print:rounded-none print:border-0 print:shadow-none">
        <div class="flex items-center justify-between bg-slate-800 px-6 py-2 text-[11px] font-semibold uppercase tracking-wider text-white">
            <span>{{ __('Invoice') }}</span>
            <span class="font-mono normal-case tracking-normal text-slate-300">{{ __('No.') }}: {{ $invoice->invoice_number }}</span>
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
                    <div class="text-2xl font-bold tracking-widest text-gray-800">{{ __('INVOICE') }}</div>
                    <div class="text-sm text-gray-500">{{ $invoice->billing_period_start->format('d M') }} – {{ $invoice->billing_period_end->format('d M Y') }}</div>
                </div>
            </div>

            {{-- Billed to / invoice details --}}
            <div class="grid grid-cols-1 gap-6 border-b border-gray-200 py-6 text-sm sm:grid-cols-2">
                <div>
                    <h6 class="mb-3 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Billed To') }}</h6>
                    <dl class="grid grid-cols-2 gap-y-2">
                        <dt class="text-[11px] uppercase text-gray-400">{{ __('Payer') }}</dt>
                        <dd class="font-medium text-gray-800">
                            {{ $invoice->payer_name ?? '—' }}
                            <span class="block text-xs text-gray-400">{{ $invoice->payer_type === 'local_authority' ? __('Local Authority') : __('Self-funded (client/family)') }}</span>
                        </dd>

                        <dt class="text-[11px] uppercase text-gray-400">{{ __('Service User') }}</dt>
                        <dd class="font-medium text-gray-800">{{ $invoice->serviceUser->name ?? '—' }}</dd>

                        @if ($invoice->payer_address)
                            <dt class="text-[11px] uppercase text-gray-400">{{ __('Address') }}</dt>
                            <dd class="font-medium text-gray-800">{{ $invoice->payer_address }}</dd>
                        @endif

                        @if ($invoice->local_authority_reference)
                            <dt class="text-[11px] uppercase text-gray-400">{{ __('LA Reference') }}</dt>
                            <dd class="font-medium text-gray-800">{{ $invoice->local_authority_reference }}</dd>
                        @endif
                    </dl>
                </div>
                <div>
                    <h6 class="mb-3 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Invoice Details') }}</h6>
                    <dl class="grid grid-cols-2 gap-y-2">
                        <dt class="text-[11px] uppercase text-gray-400">{{ __('Billing Period') }}</dt>
                        <dd class="font-medium text-gray-800">
                            {{ $invoice->billing_period_start->format('d M Y') }} – {{ $invoice->billing_period_end->format('d M Y') }}
                        </dd>

                        <dt class="text-[11px] uppercase text-gray-400">{{ __('Issue Date') }}</dt>
                        <dd class="font-medium text-gray-800">{{ $invoice->issued_at?->format('d M Y') ?? '—' }}</dd>

                        @if ($invoice->payment_reference)
                            <dt class="text-[11px] uppercase text-gray-400">{{ __('Payment Ref.') }}</dt>
                            <dd class="font-medium text-gray-800">{{ $invoice->payment_reference }}</dd>
                        @endif
                    </dl>
                    @if ($counterpart)
                        <p class="mt-3 text-xs text-gray-400">
                            {{ __('Part of a split bill — the balance is invoiced separately to') }}
                            {{ $counterpart->payer_type === 'local_authority' ? __('the local authority') : __('the client/family') }}
                            ({{ $counterpart->invoice_number }}).
                        </p>
                    @endif
                </div>
            </div>

            {{-- Line items --}}
            <div class="py-6">
                <table class="w-full border-t-2 border-primary-500 text-sm">
                    <thead>
                        <tr class="text-[11px] uppercase text-gray-400">
                            <th class="py-1 text-left font-normal">{{ __('Description') }}</th>
                            <th class="py-1 text-right font-normal">{{ __('Qty / Hrs') }}</th>
                            <th class="py-1 text-right font-normal">{{ __('Rate') }}</th>
                            <th class="py-1 text-right font-normal">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($invoice->lines as $line)
                            <tr wire:key="line-{{ $line->id }}">
                                <td class="py-2">{{ $line->description }}</td>
                                <td class="py-2 text-right text-gray-500">{{ $line->quantity !== null ? number_format($line->quantity, 2) : '—' }}</td>
                                <td class="py-2 text-right text-gray-500">{{ $line->rate !== null ? number_format($line->rate, 2) : '—' }}</td>
                                <td class="py-2 text-right {{ $line->amount < 0 ? 'text-amber-600' : '' }}">
                                    {{ number_format($line->amount, 2) }}
                                    @if ($line->line_type === 'adjustment' && $invoice->status === 'draft' && $this->canManage())
                                        <button type="button" wire:click="removeLine('{{ $line->id }}')" wire:confirm="{{ __('Remove this adjustment?') }}"
                                                class="ml-1 text-gray-300 hover:text-accent-500 print:hidden"><i class="ik ik-x"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-200 text-gray-600">
                            <td colspan="3" class="py-2">{{ __('Subtotal') }}</td>
                            <td class="py-2 text-right">{{ number_format($invoice->subtotal, 2) }}</td>
                        </tr>
                        @if ($invoice->tax_amount > 0)
                            <tr class="text-gray-600">
                                <td colspan="3" class="py-2">{{ __('Tax') }}</td>
                                <td class="py-2 text-right">{{ number_format($invoice->tax_amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="border-t border-gray-200 font-semibold text-gray-800">
                            <td colspan="3" class="py-2">{{ __('Total') }}</td>
                            <td class="py-2 text-right">{{ number_format($invoice->total_amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Amount due callout --}}
            <div class="mt-2 flex justify-end">
                <div class="rounded-lg border border-primary-200 bg-primary-50 px-6 py-3 text-right">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-primary-700">{{ __('Amount Due') }}</div>
                    <div class="text-2xl font-bold text-primary-700">{{ number_format($invoice->total_amount, 2) }}</div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="mt-10 border-t border-gray-100 pt-4 text-center text-[11px] text-gray-400">
                <p>{{ __('This is a computer-generated invoice. Care delivered is itemized above against completed visits or an agreed recurring care package. All figures are shown in your agency\'s standard currency; no tax has been applied unless shown above.') }}</p>
                <p class="mt-1">
                    {{ __('Generated') }}: {{ now()->format('d M Y, H:i') }}
                    &nbsp;·&nbsp; {{ __('Page 1 of 1') }}
                    &nbsp;·&nbsp; &copy; {{ now()->year }} {{ $agency?->name ?? config('app.name') }}
                </p>
            </div>
        </div>
    </div>

    <x-drawer name="invoice-adjustment-form" title="{{ __('Add adjustment') }}" width="w-[26rem]">
        <div class="space-y-4">
            <x-form.input name="adjustmentDescription" label="{{ __('Description') }}" wire:model="adjustmentDescription" required />
            <x-form.input type="number" step="0.01" name="adjustmentAmount" label="{{ __('Amount (negative to discount)') }}" wire:model="adjustmentAmount" required />
        </div>

        <x-slot:footer>
            <x-button wire:click="addAdjustment">{{ __('Add') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-drawer name="invoice-paid-form" title="{{ __('Mark as paid') }}" width="w-[24rem]">
        <div class="space-y-4">
            <x-form.input name="paymentReference" label="{{ __('Payment reference (optional)') }}" wire:model="paymentReference" />
        </div>

        <x-slot:footer>
            <x-button wire:click="markPaid">{{ __('Mark as paid') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <style>
        @media print {
            body * { visibility: hidden; }
            #invoice-document, #invoice-document * { visibility: visible; }
            #invoice-document {
                position: absolute;
                inset: 0;
                width: 100%;
                margin: 0;
                max-width: none;
            }
        }
    </style>
</div>
