<div>
    <x-page-header :title="(__('Reports'))" :subtitle="(__('Every report reads straight from the ledger, so they always agree with each other'))" icon="ik ik-bar-chart-2"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Reports' => null]" />
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($reports as $key => $r)
            <a href="{{ route('accounts.reports.show', $key) }}" wire:navigate class="group rounded-xl border border-gray-100 bg-white p-5 shadow-sm transition hover:border-primary-200">
                <span class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-500/10 text-primary-600"><i class="{{ $r['icon'] }}"></i></span>
                <h3 class="font-semibold text-gray-800">{{ $r['title'] }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ $r['desc'] }}</p>
            </a>
        @endforeach
    </div>
    {{-- Batch 44b --}}
    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        <a href="{{ route('accounts.notes') }}" wire:navigate class="flex items-center gap-4 rounded-xl border border-gray-100 bg-white p-5 shadow-sm transition hover:border-primary-200">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-500/10 text-primary-600"><i class="ik ik-edit-3"></i></span>
            <span><span class="block font-semibold text-gray-800">{{ __('Notes to the accounts') }}</span><span class="text-sm text-gray-500">{{ __('Accounting policies and other written notes, per year') }}</span></span>
        </a>
        <a href="{{ route('accounts.years') }}" wire:navigate class="flex items-center gap-4 rounded-xl border border-gray-100 bg-white p-5 shadow-sm transition hover:border-primary-200">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-500/10 text-primary-600"><i class="ik ik-printer"></i></span>
            <span><span class="block font-semibold text-gray-800">{{ __('Audit pack') }}</span><span class="text-sm text-gray-500">{{ __('Every statement, the notes, trial balance and ledgers for a year in one printable document — from Financial years') }}</span></span>
        </a>
    </div>
</div>
