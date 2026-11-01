<div>
    <x-page-header :title="(__('Financial years'))" :subtitle="(__('Your accounting years start on :d each year (change it in Settings)', ['d' => \Illuminate\Support\Carbon::create(2000, $cur->fy_start_month, $cur->fy_start_day)->format('j F')]))" icon="ik ik-calendar"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Financial years' => null]">
        <x-button variant="secondary" wire:click="addPrevious"><i class="ik ik-chevrons-left"></i>{{ __('Add earlier year') }}</x-button>
        <x-button wire:click="addNext"><i class="ik ik-plus"></i>{{ __('Add next year') }}</x-button>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <x-card no-padding>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">{{ __('Year') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Dates') }}</th>
                            <th class="px-5 py-3 text-right font-medium">{{ __('Entries') }}</th>
                            <th class="px-5 py-3 text-right font-medium">{{ $cur->term('result_short') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($years as $y)
                            <tr wire:key="fy-{{ $y->id }}">
                                <td class="px-5 py-3 font-medium text-gray-700">{{ $y->name }}@if ($y->contains(today()))<x-badge color="primary" class="ml-2">{{ __('Current') }}</x-badge>@endif</td>
                                <td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ $y->starts_on->format('d M Y') }} – {{ $y->ends_on->format('d M Y') }}</td>
                                <td class="px-5 py-3 text-right text-gray-600">{{ $y->journal_count }}</td>
                                <td @class(['whitespace-nowrap px-5 py-3 text-right font-medium', 'text-accent-600' => $y->result < 0, 'text-gray-700' => $y->result >= 0])>{{ $cur->money($y->result) }}</td>
                                <td class="px-5 py-3">
                                    <x-badge :color="($y->isClosed() ? 'dark' : 'success')">{{ $y->isClosed() ? __('Closed') : __('Open') }}</x-badge>
                                    @if ($y->closed_at)<span class="block text-xs text-gray-400">{{ $y->closed_at->format('d M Y') }}</span>@endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm">
                                    <a href="{{ route('accounts.reports.show', ['report' => 'income-statement', 'year' => $y->id]) }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Statements') }}</a>
                                    @if ($y->isClosed())
                                        <button type="button" wire:click="askReopen('{{ $y->id }}')" class="ml-3 text-gray-500 hover:underline">{{ __('Re-open') }}</button>
                                    @elseif (! $y->ends_on->isFuture())
                                        <button type="button" wire:click="close('{{ $y->id }}')" wire:confirm="{{ __('Close :y? Nothing can then be posted into it, and the books are locked up to :d.', ['y' => $y->name, 'd' => $y->ends_on->format('d M Y')]) }}" class="ml-3 text-gray-500 hover:underline">{{ __('Close year') }}</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-card>
            <p class="mt-3 text-xs text-gray-400">{{ __('Closing a year locks it. Year-end appropriations (reserves, dividends, transfers to retained earnings) and the audit pack come in the next update.') }}</p>
        </div>

        <div class="space-y-6">
            <x-card :title="(__('Lock date'))" :subtitle="(__('Nothing on or before this date can be posted or changed'))" icon="ik ik-lock">
                <form wire:submit="saveLock" class="space-y-3">
                    <x-form.input type="date" name="lockDate" wire:model="lockDate" />
                    <p class="text-xs text-gray-400">{{ __('Set it after each month’s bank reconciliation, or when the auditors sign off. Leave empty for no lock.') }}</p>
                    <x-button type="submit" class="w-full justify-center">{{ __('Save lock date') }}</x-button>
                </form>
            </x-card>
            <x-card no-padding :title="(__('Audit trail'))">
                <div class="divide-y divide-gray-50">
                    @forelse ($log as $a)
                        <div class="px-5 py-2.5 text-sm">
                            <span class="text-gray-700">{{ $a->description }}</span>
                            @if ($a->properties['reason'] ?? null)<span class="block text-xs text-gray-500">“{{ $a->properties['reason'] }}”</span>@endif
                            <span class="block text-xs text-gray-400">{{ $a->created_at->format('d M Y H:i') }}{{ $a->causer ? ' · '.$a->causer->name : '' }}</span>
                        </div>
                    @empty
                        <p class="px-5 py-5 text-center text-sm text-gray-400">{{ __('Nothing yet.') }}</p>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>

    <x-drawer name="reopen" :title="(__('Re-open year'))">
        <form wire:submit="reopen" id="reopen-form" class="space-y-4">
            <p class="text-sm text-gray-600">{{ __('Only re-open a year to correct it before the accounts are final. The lock date moves back to the day before the year starts.') }}</p>
            <x-form.textarea name="reopenReason" :label="(__('Reason'))" wire:model="reopenReason" rows="3" required />
        </form>
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'reopen')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="reopen-form">{{ __('Re-open') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
