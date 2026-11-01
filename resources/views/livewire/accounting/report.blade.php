<div>
    @php
        $p = $rep['params'];
        $period = in_array($report, ['income-statement', 'ledger']);
        $asAtOnly = in_array($report, ['balance-sheet', 'trial-balance', 'aged-receivables', 'aged-payables']);
    @endphp
    <x-page-header :title="($rep['title'])" :subtitle="($rep['subtitle'])" :icon="(\App\Services\Accounting\ReportBuilder::REPORTS[$report][2])"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Reports' => route('accounts.reports'), $rep['title'] => null]">
        <x-button variant="secondary" :href="(route('accounts.reports.csv', $query))"><i class="ik ik-download"></i>{{ __('CSV') }}</x-button>
        <x-button :href="(route('accounts.reports.print', $query))" target="_blank"><i class="ik ik-printer"></i>{{ __('Print / PDF') }}</x-button>
    </x-page-header>

    <x-card class="mb-6">
        <div class="flex flex-wrap items-end gap-4">
            @if ($report !== 'ledger')
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-500">{{ __('Financial year') }}</label>
                    <select wire:model.live="year" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500">
                        <option value="">{{ __('Current year') }}</option>
                        @foreach ($years as $y)<option value="{{ $y->id }}">{{ $y->name }}</option>@endforeach
                    </select>
                </div>
            @else
                <div style="min-width:16rem">
                    <label class="mb-1.5 block text-xs font-medium text-gray-500">{{ __('Account') }}</label>
                    <select wire:model.live="account" class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500">
                        @foreach ($accountGroups as $type => $group)
                            <optgroup label="{{ __(\App\Models\Accounting\Account::TYPES[$type]) }}">
                                @foreach ($group as $a)<option value="{{ $a->id }}">{{ $a->code }} · {{ $a->name }}</option>@endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            @endif
            @if ($period)
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-500">{{ __('From') }}</label>
                    <input type="date" wire:model.live="from" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 outline-none focus:border-primary-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-500">{{ __('To') }}</label>
                    <input type="date" wire:model.live="to" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 outline-none focus:border-primary-500">
                </div>
            @endif
            @if ($asAtOnly)
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-500">{{ __('As at') }}</label>
                    <input type="date" wire:model.live="asAt" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 outline-none focus:border-primary-500">
                </div>
            @endif
            @if (in_array($report, ['income-statement', 'balance-sheet']))
                <label class="flex items-center gap-2 pb-2 text-sm text-gray-600"><input type="checkbox" wire:model.live="compare" class="rounded border-gray-300 text-primary-600">{{ __('Compare with last year') }}</label>
            @endif
            <span wire:loading class="pb-2 text-xs text-gray-400">{{ __('Updating…') }}</span>
        </div>
    </x-card>

    @include('accounting.reports._style')
    <div class="overflow-x-auto rounded-xl border border-gray-100 bg-white p-5 shadow-sm p-6">
        <div class="mx-auto max-w-4xl">
            @include($rep['view'])
        </div>
    </div>
</div>
