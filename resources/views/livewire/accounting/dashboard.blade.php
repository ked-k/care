<div>
    <x-page-header :title="(__('Finance & Accounts'))" :subtitle="($cur->legal_name).' · '.($year->name).' ('.($year->starts_on->format('d M Y')).' – '.($year->ends_on->format('d M Y')).')'" icon="ik ik-pie-chart"
                   :breadcrumbs="['Finance' => null]">
        <x-dropdown>
            <x-slot:trigger>
                <x-button><i class="ik ik-plus"></i>{{ __('New') }}<i class="ik ik-chevron-down text-xs"></i></x-button>
            </x-slot:trigger>
            <a href="{{ route('accounts.documents.create', 'invoice') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="ik ik-file-text mr-2 text-gray-400"></i>{{ __('Sales invoice') }}</a>
            <a href="{{ route('accounts.receipts', ['new' => 1]) }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="ik ik-download mr-2 text-gray-400"></i>{{ __('Customer receipt') }}</a>
            <a href="{{ route('accounts.documents.create', 'bill') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="ik ik-file-minus mr-2 text-gray-400"></i>{{ __('Supplier bill') }}</a>
            <a href="{{ route('accounts.payments', ['new' => 1]) }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="ik ik-upload mr-2 text-gray-400"></i>{{ __('Supplier payment') }}</a>
            <a href="{{ route('accounts.documents.create', 'money_out') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="ik ik-shopping-cart mr-2 text-gray-400"></i>{{ __('Spend money (expense)') }}</a>
            <a href="{{ route('accounts.documents.create', 'money_in') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="ik ik-plus-circle mr-2 text-gray-400"></i>{{ __('Receive money') }}</a>
            <a href="{{ route('accounts.banking', ['transfer' => 1]) }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="ik ik-repeat mr-2 text-gray-400"></i>{{ __('Bank transfer') }}</a>
            <a href="{{ route('accounts.journals.create') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="ik ik-book mr-2 text-gray-400"></i>{{ __('Journal entry') }}</a>
        </x-dropdown>
    </x-page-header>

    @if ($isEmpty)
        <x-card class="mb-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary-500/10 text-primary-600"><i class="ik ik-flag text-xl"></i></span>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-800">{{ __('Your books are set up and empty') }}</h3>
                    <p class="text-sm text-gray-500">{{ __('Check the currency and financial year in Settings, then post your care invoices, payroll and shop activity from the date you choose. Anything from before that date goes in as opening balances (a journal entry).') }}</p>
                </div>
                <x-button :href="(route('accounts.settings'))" wire:navigate>{{ __('Open settings') }}</x-button>
            </div>
        </x-card>
    @endif

    @if ($issues->isNotEmpty())
        <x-card class="mb-6" :title="(__('Waiting to post'))" :subtitle="(__('These records were saved, but couldn’t go into the books yet.'))" icon="ik ik-alert-triangle">
            <div class="divide-y divide-gray-100">
                @foreach ($issues as $i)
                    <div class="flex flex-col gap-2 py-2.5 sm:flex-row sm:items-center" wire:key="iss-{{ $i->id }}">
                        <div class="min-w-0 flex-1">
                            <span class="font-medium text-gray-700">{{ $i->sourceLabel() }} {{ $i->label }}</span>
                            <span class="block text-sm text-accent-600">{{ $i->message }}</span>
                        </div>
                        <span class="text-xs text-gray-400">{{ trans_choice(':n try|:n tries', $i->attempts, ['n' => $i->attempts]) }} · {{ $i->updated_at->diffForHumans() }}</span>
                        <x-button size="sm" variant="secondary" wire:click="retry('{{ $i->id }}')">{{ __('Retry') }}</x-button>
                    </div>
                @endforeach
            </div>
            @if ($issues->count() > 1)
                <div class="mt-3 text-right"><x-button size="sm" wire:click="retryAll">{{ __('Retry all') }}</x-button></div>
            @endif
        </x-card>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card :value="($cur->money($cash))" :label="(__('Cash & bank'))" icon="ik ik-credit-card" color="primary" />
        <x-stat-card :value="($cur->money($owed))" :label="(__('Customers owe you'))" icon="ik ik-download" color="green" />
        <x-stat-card :value="($cur->money($owing))" :label="(__('You owe suppliers'))" icon="ik ik-upload" color="amber" />
        <x-stat-card :value="($cur->money($result))" :label="($cur->term('result_short')).' '.($year->name)" icon="ik ik-trending-up" :color="($result >= 0 ? 'cyan' : 'accent')" />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-card :title="($cur->term('income')).' '.(__('and')).' '.(mb_strtolower($cur->term('expenses')))" :subtitle="(__('Last six months'))">
                @php $peak = max(1, collect($months)->flatMap(fn ($m) => [$m['income'], $m['expense']])->max()); @endphp
                <div class="flex h-48 items-end gap-3">
                    @foreach ($months as $m)
                        <div class="flex h-full flex-1 flex-col justify-end">
                            <div class="flex flex-1 items-end justify-center gap-1">
                                <div class="rounded-t bg-green-500" style="width:33%;height: {{ max(2, round(max(0, $m['income']) / $peak * 100, 1)) }}%" title="{{ $cur->term('income') }}: {{ $cur->money($m['income']) }}"></div>
                                <div class="rounded-t bg-accent-500" style="width:33%;height: {{ max(2, round(max(0, $m['expense']) / $peak * 100, 1)) }}%" title="{{ $cur->term('expenses') }}: {{ $cur->money($m['expense']) }}"></div>
                            </div>
                            <span class="mt-1.5 text-center text-xs text-gray-400">{{ $m['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded bg-green-500"></span>{{ $cur->term('income') }}</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded bg-accent-500"></span>{{ $cur->term('expenses') }}</span>
                </div>
            </x-card>

            <x-card no-padding :title="(__('Latest entries'))">
                <div class="divide-y divide-gray-50">
                    @forelse ($recent as $j)
                        <a href="{{ route('accounts.journals.show', $j->id) }}" wire:navigate class="flex items-center gap-3 px-5 py-2.5 text-sm hover:bg-gray-50">
                            <span class="w-20 shrink-0 text-gray-400">{{ $j->date->format('d M') }}</span>
                            <span class="min-w-0 flex-1 truncate text-gray-700">{{ $j->memo ?: $j->typeLabel() }}</span>
                            <x-badge>{{ $j->typeLabel() }}</x-badge>
                            <span class="w-28 shrink-0 text-right font-medium text-gray-700">{{ $cur->money($j->total) }}</span>
                        </a>
                    @empty
                        <div class="px-5 py-8"><x-empty-state compact :title="(__('Nothing posted yet'))" icon="ik ik-book" /></div>
                    @endforelse
                </div>
                <div class="border-t border-gray-100 px-5 py-2.5 text-right"><a href="{{ route('accounts.journals') }}" wire:navigate class="text-sm font-medium text-primary-600 hover:underline">{{ __('All journals') }}</a></div>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card :title="(__('Needs attention'))">
                <div class="space-y-3 text-sm">
                    <a href="{{ route('accounts.invoices', ['status' => 'overdue']) }}" wire:navigate class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5 hover:bg-gray-100">
                        <span><i class="ik ik-alert-circle mr-1 {{ $overdueCount ? 'text-accent-500' : 'text-gray-400' }}"></i>{{ trans_choice(':n overdue invoice|:n overdue invoices', $overdueCount, ['n' => $overdueCount]) }}</span>
                        <span class="font-semibold text-gray-700">{{ $cur->money($overdueTotal) }}</span>
                    </a>
                    <a href="{{ route('accounts.bills', ['status' => 'open']) }}" wire:navigate class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5 hover:bg-gray-100">
                        <span><i class="ik ik-clock mr-1 text-gray-400"></i>{{ __('Bills due within 7 days') }}</span>
                        <span class="font-semibold text-gray-700">{{ $cur->money($billsDue) }}</span>
                    </a>
                </div>
            </x-card>

            <x-card no-padding :title="(__('Bank & cash'))">
                <div class="divide-y divide-gray-50">
                    @foreach ($money as $m)
                        <a href="{{ route('accounts.reports.show', ['report' => 'ledger', 'account' => $m['account']->id]) }}" wire:navigate class="flex items-center justify-between px-5 py-2.5 text-sm hover:bg-gray-50">
                            <span class="text-gray-700">{{ $m['account']->name }}<span class="block text-xs text-gray-400">{{ $m['account']->subtypeLabel() }}{{ $m['account']->account_number ? ' · '.$m['account']->account_number : '' }}</span></span>
                            <span @class(['font-semibold', 'text-accent-600' => $m['balance'] < 0, 'text-gray-700' => $m['balance'] >= 0])>{{ $cur->money($m['balance']) }}</span>
                        </a>
                    @endforeach
                </div>
            </x-card>

            <x-card :title="(__('Reports'))">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    @foreach (['income-statement' => $cur->term('income_statement'), 'balance-sheet' => $cur->term('balance_sheet'), 'trial-balance' => __('Trial balance'), 'aged-receivables' => __('Aged debtors')] as $r => $label)
                        <a href="{{ route('accounts.reports.show', $r) }}" wire:navigate class="rounded-lg border border-gray-100 px-3 py-2 text-gray-600 hover:border-primary-200 hover:text-primary-600">{{ $label }}</a>
                    @endforeach
                </div>
            </x-card>
        </div>
    </div>
</div>
