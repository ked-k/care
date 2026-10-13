<div>
    <x-page-header title="{{ __('Invoicing') }}" subtitle="{{ __('Billing runs for service users, local authorities, and families') }}"
                    icon="ik ik-file-text" :breadcrumbs="['Home' => url('dashboard'), 'Invoicing' => null]">
        <x-button variant="primary" @click="$dispatch('open-drawer', 'new-invoice-run')">
            <i class="ik ik-plus mr-1"></i>{{ __('New invoice run') }}
        </x-button>
    </x-page-header>

    <x-card no-padding hover>
        <x-table :paginator="$runs" title="{{ __('Invoice Runs') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Reference') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Billing Period') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Invoices') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Total Billed') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($runs as $run)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="run-{{ $run->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">{{ $run->reference }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                {{ $run->billing_period_start->format('d M') }} – {{ $run->billing_period_end->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $run->invoices_count }}</td>
                            <td class="px-5 py-3 font-medium text-primary-600">{{ number_format($run->total_billed, 2) }}</td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ match($run->status) {
                                    'draft' => 'secondary',
                                    'issued' => 'primary',
                                    'paid' => 'green',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($run->status) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('invoicing.show', $run) }}" wire:navigate
                                   class="text-primary-600 hover:underline text-sm font-medium">{{ __('Open') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-empty-state title="{{ __('No invoice runs yet') }}"
                                               description="{{ __('Create a run to bill completed visits and care packages for a billing period.') }}"
                                               icon="ik ik-file-text" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>

    <x-drawer name="new-invoice-run" title="{{ __('New invoice run') }}" width="w-[28rem]">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="newPeriodStart" label="{{ __('Period start') }}" wire:model="newPeriodStart" required />
                <x-form.input type="date" name="newPeriodEnd" label="{{ __('Period end') }}" wire:model="newPeriodEnd" required />
            </div>
            <x-form.select name="newFrequency" label="{{ __('Frequency') }}" wire:model="newFrequency" required>
                <option value="weekly">{{ __('Weekly') }}</option>
                <option value="monthly">{{ __('Monthly') }}</option>
            </x-form.select>
        </div>

        <x-slot:footer>
            <x-button wire:click="createRun">{{ __('Create run') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
