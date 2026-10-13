<div>
    <x-page-header title="{{ __('Invoice Run') }} {{ $run->reference }}"
                    subtitle="{{ $run->billing_period_start->format('d M') }} – {{ $run->billing_period_end->format('d M Y') }}"
                    icon="ik ik-file-text"
                    :breadcrumbs="['Home' => url('dashboard'), 'Invoicing' => route('invoicing.index'), $run->reference => null]">
        <div class="flex items-center gap-2">
            <x-badge color="{{ match($run->status) {
                'draft' => 'secondary',
                'issued' => 'primary',
                'paid' => 'green',
                default => 'secondary',
            } }}">
                {{ ucfirst($run->status) }}
            </x-badge>

            @if ($run->status === 'draft')
                <x-button variant="outline" size="sm" wire:click="generateInvoices">{{ __('Generate invoices') }}</x-button>
            @endif

            @if ($run->status === 'draft' && $run->invoices->isNotEmpty())
                <x-button variant="primary" size="sm" wire:click="issueRun">{{ __('Issue run') }}</x-button>
            @endif

            @if ($run->status === 'issued')
                <x-button variant="success" size="sm" wire:click="markAllPaid"
                           wire:confirm="{{ __('Mark every invoice in this run as paid?') }}">{{ __('Mark all paid') }}</x-button>
            @endif
        </div>
    </x-page-header>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 mb-5">
        <x-stat-card value="{{ number_format($run->total_billed, 2) }}" label="{{ __('Total Billed') }}" icon="ik ik-dollar-sign" color="primary" />
        <x-stat-card value="{{ $run->invoices->count() }}" label="{{ __('Invoices') }}" icon="ik ik-file-text" color="green" />
    </div>

    <x-card no-padding hover>
        <x-slot:header>{{ __('Invoices') }}</x-slot:header>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Invoice #') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Service User') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Payer') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Total') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($run->invoices as $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="inv-{{ $invoice->id }}">
                            <td class="px-5 py-3 font-mono text-xs text-gray-500">{{ $invoice->invoice_number }}</td>
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">{{ $invoice->serviceUser->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                {{ $invoice->payer_type === 'local_authority' ? __('Local Authority') : __('Self-funded') }}
                                <span class="block text-xs text-gray-400">{{ $invoice->payer_name }}</span>
                            </td>
                            <td class="px-5 py-3 font-medium text-primary-600">{{ number_format($invoice->total_amount, 2) }}</td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ match($invoice->status) {
                                    'draft' => 'secondary',
                                    'issued' => 'primary',
                                    'paid' => 'green',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($invoice->status) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('invoices.show', $invoice) }}" wire:navigate
                                   class="text-primary-600 hover:underline text-sm font-medium">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-empty-state title="{{ __('No invoices yet') }}"
                                               description="{{ __('Generate invoices from completed visits and care packages in this billing period.') }}"
                                               icon="ik ik-file-text" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
