<div>
    @php $customer = $c->is_customer || ! $c->is_supplier; $sign = $statement['sign']; @endphp
    <x-page-header :title="($c->name)" :subtitle="($c->roleLabel()).($c->serviceUser ? ' · '.__('service user :n', ['n' => $c->serviceUser->name]) : '')"
                   :icon="'ik ik-'.($customer ? 'user' : 'truck')"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), ($customer ? 'Customers' : 'Suppliers') => route($customer ? 'accounts.customers' : 'accounts.suppliers'), $c->name => null]">
        @if ($c->is_customer)
            <x-button variant="secondary" :href="(route('accounts.receipts', ['new' => 1, 'contact' => $c->id]))" wire:navigate><i class="ik ik-download"></i>{{ __('Receive payment') }}</x-button>
            <x-button :href="(route('accounts.documents.create', ['kind' => 'invoice', 'contact' => $c->id]))" wire:navigate><i class="ik ik-plus"></i>{{ __('New invoice') }}</x-button>
        @endif
        @if ($c->is_supplier)
            <x-button variant="secondary" :href="(route('accounts.payments', ['new' => 1, 'contact' => $c->id]))" wire:navigate><i class="ik ik-upload"></i>{{ __('Pay supplier') }}</x-button>
            <x-button :href="(route('accounts.documents.create', ['kind' => 'bill', 'contact' => $c->id]))" wire:navigate><i class="ik ik-plus"></i>{{ __('New bill') }}</x-button>
        @endif
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-card no-padding>
                <div class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="font-semibold text-gray-700">{{ __('Statement') }}</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="date" wire:model.live="from" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-600 outline-none focus:border-primary-500">
                        <span class="text-gray-400">–</span>
                        <input type="date" wire:model.live="to" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-600 outline-none focus:border-primary-500">
                        <x-button size="sm" variant="secondary" :href="(route('accounts.contacts.statement', ['contactId' => $c->id, 'from' => $from, 'to' => $to]))" target="_blank"><i class="ik ik-printer"></i>{{ __('Print') }}</x-button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                                <th class="px-5 py-2.5 font-medium">{{ __('Date') }}</th>
                                <th class="px-5 py-2.5 font-medium">{{ __('Details') }}</th>
                                <th class="px-5 py-2.5 text-right font-medium">{{ $customer ? __('Invoiced') : __('Paid') }}</th>
                                <th class="px-5 py-2.5 text-right font-medium">{{ $customer ? __('Paid') : __('Billed') }}</th>
                                <th class="px-5 py-2.5 text-right font-medium">{{ __('Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr class="bg-gray-50/60"><td class="px-5 py-2.5 text-gray-500">{{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }}</td><td class="px-5 py-2.5 text-gray-500" colspan="3">{{ __('Balance brought forward') }}</td><td class="px-5 py-2.5 text-right font-medium">{{ $cur->money($statement['opening'] * $sign) }}</td></tr>
                            @foreach ($statement['lines'] as $l)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-2.5 text-gray-500">{{ $l->date->format('d M Y') }}</td>
                                    <td class="px-5 py-2.5 text-gray-700">
                                        @if ($url = $l->journal->sourceUrl())<a href="{{ $url }}" wire:navigate class="hover:text-primary-600">{{ $l->journal->reference ?: $l->journal->number }}</a>@else{{ $l->journal->reference ?: $l->journal->number }}@endif
                                        <span class="block text-xs text-gray-400">{{ $l->journal->typeLabel() }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-2.5 text-right">{{ (float) $l->debit ? $cur->money($l->debit) : '' }}</td>
                                    <td class="whitespace-nowrap px-5 py-2.5 text-right">{{ (float) $l->credit ? $cur->money($l->credit) : '' }}</td>
                                    <td class="whitespace-nowrap px-5 py-2.5 text-right font-medium">{{ $cur->money($l->running * $sign) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr class="border-t border-gray-200"><td colspan="4" class="px-5 py-3 font-semibold text-gray-700">{{ $customer ? __('Owed to you') : __('You owe') }} {{ __('at :d', ['d' => \Illuminate\Support\Carbon::parse($to)->format('d M Y')]) }}</td><td class="px-5 py-3 text-right text-base font-bold text-gray-800">{{ $cur->money($statement['closing'] * $sign) }}</td></tr></tfoot>
                    </table>
                </div>
            </x-card>
        </div>
        <div class="space-y-6">
            <x-card :title="(__('Details'))">
                <dl class="space-y-2 text-sm">
                    @foreach (['contact_name' => __('Contact'), 'email' => __('Email'), 'phone' => __('Phone'), 'tax_number' => __('VAT / tax no.')] as $k => $lbl)
                        @if ($c->{$k})<div class="flex justify-between gap-3"><dt class="text-gray-400">{{ $lbl }}</dt><dd class="text-right text-gray-700">{{ $c->{$k} }}</dd></div>@endif
                    @endforeach
                    <div class="flex justify-between gap-3"><dt class="text-gray-400">{{ __('Terms') }}</dt><dd class="text-gray-700">{{ __(':n days', ['n' => $c->payment_terms_days ?? $cur->payment_terms_days]) }}</dd></div>
                    @if ($c->address)<div><dt class="text-gray-400">{{ __('Address') }}</dt><dd class="whitespace-pre-line text-gray-700">{{ $c->address }}</dd></div>@endif
                    @if ($c->notes)<div><dt class="text-gray-400">{{ __('Notes') }}</dt><dd class="whitespace-pre-line text-gray-600">{{ $c->notes }}</dd></div>@endif
                </dl>
            </x-card>
            <x-card no-padding :title="(__('Open items'))">
                <div class="divide-y divide-gray-50">
                    @forelse ($open as $d)
                        <a href="{{ route('accounts.documents.show', $d->id) }}" wire:navigate class="flex items-center justify-between px-5 py-2.5 text-sm hover:bg-gray-50">
                            <span><span class="font-medium text-gray-700">{{ $d->number }}</span><span class="block text-xs {{ $d->isOverdue() ? 'text-accent-600' : 'text-gray-400' }}">{{ __('due :d', ['d' => $d->due_date?->format('d M Y') ?? '—']) }}</span></span>
                            <span class="font-semibold text-gray-700">{{ $cur->money($d->balance()) }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-5 text-center text-sm text-gray-400">{{ __('Nothing open.') }}</p>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</div>
