<div>
    @php
        $listRoute = match ($doc->kind) { 'bill' => 'accounts.bills', 'invoice' => 'accounts.invoices', default => 'accounts.money' };
        $listName = match ($doc->kind) { 'bill' => 'Supplier bills', 'invoice' => 'Sales invoices', default => 'Money in / out' };
        $agency = auth()->user()->agency;
    @endphp
    <x-page-header :title="($doc->kindLabel()).' '.($doc->number)" :subtitle="($doc->contact?->name ?? ($doc->bankAccount?->name ?? ''))"
                   :icon="(\App\Models\Accounting\Document::KINDS[$doc->kind][2])"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), $listName => route($listRoute), $doc->number => null]">
        <x-badge :color="($doc->statusColor())" class="px-3 py-1 text-sm">{{ $doc->statusLabel() }}</x-badge>
        @if ($doc->isDraft())
            <x-button variant="secondary" :href="(route('accounts.documents.edit', $doc->id))" wire:navigate><i class="ik ik-edit-2"></i>{{ __('Edit') }}</x-button>
            <x-button wire:click="approve" wire:confirm="{{ __('Approve and post :n? It can’t be edited afterwards.', ['n' => $doc->number]) }}"><i class="ik ik-check"></i>{{ __('Approve') }}</x-button>
        @elseif ($doc->isOpen())
            <x-button @click="$dispatch('open-drawer', 'pay-form')"><i class="ik ik-{{ $doc->kind === 'invoice' ? 'download' : 'upload' }}"></i>{{ $doc->kind === 'invoice' ? __('Receive payment') : __('Pay bill') }}</x-button>
        @endif
        <x-dropdown>
            <x-slot:trigger><x-button variant="secondary"><i class="ik ik-more-horizontal"></i></x-button></x-slot:trigger>
            <a href="{{ route('accounts.documents.print', $doc->id) }}" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="ik ik-printer mr-2 text-gray-400"></i>{{ __('Print / save as PDF') }}</a>
            @if ($doc->journal)
                <a href="{{ route('accounts.journals.show', $doc->journal_id) }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="ik ik-book mr-2 text-gray-400"></i>{{ __('See the journal (:n)', ['n' => $doc->journal->number]) }}</a>
            @endif
            @if ($doc->contact)
                <a href="{{ route('accounts.contacts.show', $doc->contact_id) }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="ik ik-user mr-2 text-gray-400"></i>{{ __('Open :n', ['n' => $doc->contact->name]) }}</a>
            @endif
            @if ($source)
                <a href="{{ $source[1] }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><i class="ik ik-link mr-2 text-gray-400"></i>{{ $source[0] }}</a>
            @endif
            @if ($doc->isDraft())
                <button type="button" wire:click="deleteDraft" wire:confirm="{{ __('Delete this draft?') }}" class="block w-full px-4 py-2 text-left text-sm text-accent-600 hover:bg-gray-50"><i class="ik ik-trash-2 mr-2"></i>{{ __('Delete draft') }}</button>
            @elseif ($doc->status !== 'void')
                <button type="button" @click="$dispatch('open-modal', 'void-doc')" class="block w-full px-4 py-2 text-left text-sm text-accent-600 hover:bg-gray-50"><i class="ik ik-slash mr-2"></i>{{ __('Void') }}</button>
            @endif
        </x-dropdown>
    </x-page-header>

    @if ($doc->status === 'void')
        <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
            <i class="ik ik-slash mr-1"></i>{{ __('Voided :d', ['d' => $doc->voided_at?->format('d M Y H:i')]) }} — {{ $doc->void_reason }}
        </div>
    @endif
    @if ($doc->sourceLabel())
        <div class="mb-4 rounded-lg border border-primary-100 bg-primary-500/10 px-4 py-3 text-sm text-gray-600">
            <i class="ik ik-link mr-1 text-primary-600"></i>{{ __('Created automatically from :s.', ['s' => mb_strtolower($doc->sourceLabel())]) }}
            @if ($doc->source_type === 'care_invoice'){{ __('Payments recorded here also mark the care invoice as paid.') }}@endif
        </div>
    @endif
    @if ($credit > 0)
        <div class="mb-4 flex flex-col gap-2 rounded-lg border border-green-200 bg-green-500/10 px-4 py-3 text-sm text-green-700 sm:flex-row sm:items-center sm:justify-between">
            <span><i class="ik ik-info mr-1"></i>{{ __(':n has :a of unmatched payments on their account.', ['n' => $doc->contact->name, 'a' => $cur->money($credit)]) }}</span>
            <x-button size="sm" variant="secondary" wire:click="applyCredit">{{ __('Use it on this :k', ['k' => $doc->kind === 'invoice' ? __('invoice') : __('bill')]) }}</x-button>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between bg-slate-800 px-6 py-2 text-[11px] font-semibold uppercase tracking-wider text-white">
                    <span>{{ $doc->kindLabel() }}</span>
                    <span class="font-mono normal-case tracking-normal text-slate-300">{{ $doc->number }}</span>
                </div>
                <div class="p-6 text-gray-800 p-6">
                    <div class="flex flex-wrap items-start justify-between gap-6 border-b border-gray-200 pb-6">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ $cur->legal_name ?: $agency?->name }}</h2>
                            @if ($cur->address ?: $agency?->address)<p class="whitespace-pre-line text-xs text-gray-500">{{ $cur->address ?: $agency?->address }}</p>@endif
                            @if ($cur->tax_number)<p class="text-xs text-gray-500">{{ __('VAT no.') }} {{ $cur->tax_number }}</p>@endif
                        </div>
                        <dl class="grid grid-cols-2 gap-2 text-sm">
                            <dt class="text-gray-400">{{ $doc->isOnAccount() ? __('Issued') : __('Date') }}</dt><dd class="text-right font-medium">{{ $doc->issue_date->format('d M Y') }}</dd>
                            @if ($doc->due_date)<dt class="text-gray-400">{{ __('Due') }}</dt><dd @class(['text-right font-medium', 'text-accent-600' => $doc->isOverdue()])>{{ $doc->due_date->format('d M Y') }}</dd>@endif
                            @if ($doc->reference)<dt class="text-gray-400">{{ __('Reference') }}</dt><dd class="text-right font-medium">{{ $doc->reference }}</dd>@endif
                            @if ($doc->bankAccount)<dt class="text-gray-400">{{ $doc->kind === 'money_out' ? __('Paid from') : __('Paid into') }}</dt><dd class="text-right font-medium">{{ $doc->bankAccount->name }}</dd>@endif
                        </dl>
                    </div>

                    @if ($doc->contact)
                        <div class="border-b border-gray-200 py-5">
                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ match ($doc->kind) { 'invoice' => __('Bill to'), 'bill' => __('From'), 'money_in' => __('Received from'), default => __('Paid to') } }}</p>
                            <p class="font-semibold">{{ $doc->contact->name }}</p>
                            @if ($doc->contact->address)<p class="whitespace-pre-line text-sm text-gray-500">{{ $doc->contact->address }}</p>@endif
                            @if ($doc->contact->email)<p class="text-sm text-gray-500">{{ $doc->contact->email }}</p>@endif
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="mt-4 w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-400">
                                    <th class="py-2 pr-3 font-medium">{{ __('Description') }}</th>
                                    <th class="py-2 pr-3 text-right font-medium">{{ __('Qty') }}</th>
                                    <th class="py-2 pr-3 text-right font-medium">{{ __('Price') }}</th>
                                    <th class="py-2 pr-3 text-right font-medium">{{ __('VAT') }}</th>
                                    <th class="py-2 text-right font-medium">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($doc->lines as $l)
                                    <tr class="align-top">
                                        <td class="py-2.5 pr-3">
                                            <span class="whitespace-pre-line">{{ $l->description ?: '—' }}</span>
                                            <span class="block text-xs text-gray-400">{{ $l->account?->label() }}</span>
                                        </td>
                                        <td class="whitespace-nowrap py-2.5 pr-3 text-right">{{ rtrim(rtrim(number_format((float) $l->qty, 3), '0'), '.') }}</td>
                                        <td class="whitespace-nowrap py-2.5 pr-3 text-right">{{ $cur->money($l->unit_price) }}@if ((float) $l->discount > 0)<span class="block text-xs text-gray-400">−{{ $cur->money($l->discount) }}</span>@endif</td>
                                        <td class="whitespace-nowrap py-2.5 pr-3 text-right text-gray-500">{{ (float) $l->vat_rate ? rtrim(rtrim(number_format((float) $l->vat_rate, 2), '0'), '.').'%' : '—' }}</td>
                                        <td class="whitespace-nowrap py-2.5 text-right font-medium">{{ $cur->money($doc->prices_include_vat ? $l->total : $l->net) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <dl class="w-full max-w-xs space-y-1.5 text-sm">
                            <div class="flex justify-between text-gray-500"><dt>{{ __('Subtotal') }}</dt><dd>{{ $cur->money($doc->subtotal) }}</dd></div>
                            <div class="flex justify-between text-gray-500"><dt>{{ __('VAT') }}</dt><dd>{{ $cur->money($doc->vat_total) }}</dd></div>
                            <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-bold"><dt>{{ __('Total') }}</dt><dd>{{ $cur->money($doc->total) }}</dd></div>
                            @if ($doc->isOnAccount() && ! $doc->isDraft() && $doc->status !== 'void')
                                <div class="flex justify-between text-gray-500"><dt>{{ __('Paid') }}</dt><dd>{{ $cur->money($doc->amount_paid) }}</dd></div>
                                <div class="flex justify-between rounded bg-gray-50 px-2 py-1.5 font-semibold"><dt>{{ __('Balance due') }}</dt><dd>{{ $cur->money($doc->balance()) }}</dd></div>
                            @endif
                        </dl>
                    </div>
                    @if ($doc->notes)
                        <p class="mt-6 whitespace-pre-line border-t border-gray-100 pt-4 text-sm text-gray-500">{{ $doc->notes }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6">
            @if ($doc->isOnAccount())
                <x-card no-padding :title="($doc->kind === 'invoice' ? __('Payments received') : __('Payments made'))">
                    <div class="divide-y divide-gray-50">
                        @forelse ($allocations as $a)
                            <a href="{{ route('accounts.payments.show', $a->payment_id) }}" wire:navigate class="flex items-center justify-between px-5 py-3 text-sm hover:bg-gray-50">
                                <span>
                                    <span class="font-medium text-gray-700">{{ $a->payment->number }}</span>
                                    <span class="block text-xs text-gray-400">{{ $a->payment->date->format('d M Y') }} · {{ $a->payment->methodLabel() }} · {{ $a->payment->bankAccount?->name }}</span>
                                </span>
                                <span class="font-semibold text-gray-700">{{ $cur->money($a->amount) }}</span>
                            </a>
                        @empty
                            <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('None yet.') }}</p>
                        @endforelse
                    </div>
                </x-card>
            @endif
            <x-card :title="(__('History'))">
                <ul class="space-y-2 text-sm text-gray-500">
                    <li><i class="ik ik-plus-circle mr-1 text-gray-400"></i>{{ __('Created :d', ['d' => $doc->created_at->format('d M Y H:i')]) }}{{ $doc->creator ? ' · '.$doc->creator->name : '' }}</li>
                    @if ($doc->posted_at)<li><i class="ik ik-check-circle mr-1 text-green-500"></i>{{ __('Posted :d', ['d' => $doc->posted_at->format('d M Y H:i')]) }}@if ($doc->journal) · <a href="{{ route('accounts.journals.show', $doc->journal_id) }}" wire:navigate class="text-primary-600 hover:underline">{{ $doc->journal->number }}</a>@endif</li>@endif
                    @if ($doc->voided_at)<li><i class="ik ik-slash mr-1 text-accent-500"></i>{{ __('Voided :d', ['d' => $doc->voided_at->format('d M Y H:i')]) }}</li>@endif
                </ul>
            </x-card>
        </div>
    </div>

    <x-drawer name="pay-form" :title="($doc->kind === 'invoice' ? __('Receive payment') : __('Pay bill'))" width="w-[28rem]">
        <form wire:submit="recordPayment" id="pay-form" class="space-y-4">
            <p class="text-sm text-gray-500">{{ $doc->number }} · {{ $doc->contact?->name }} · {{ __('balance :b', ['b' => $cur->money($doc->balance())]) }}</p>
            <x-form.input type="number" step="0.01" name="pay.amount" :label="(__('Amount'))" wire:model="pay.amount" required />
            <x-form.input type="date" name="pay.date" :label="(__('Date'))" wire:model="pay.date" required />
            <x-form.select name="pay.bank_account_id" :label="($doc->kind === 'invoice' ? __('Paid into') : __('Paid from'))" wire:model="pay.bank_account_id" required>
                @foreach ($banks as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
            </x-form.select>
            <x-form.select name="pay.method" :label="(__('How'))" wire:model="pay.method">
                @foreach (\App\Models\Accounting\Payment::METHODS as $k => $v)<option value="{{ $k }}">{{ __($v) }}</option>@endforeach
            </x-form.select>
            <x-form.input name="pay.reference" :label="(__('Reference'))" wire:model="pay.reference" :placeholder="(__('Bank reference, cheque no…'))" />
        </form>
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'pay-form')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="pay-form">{{ __('Save payment') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-modal name="void-doc" :title="(__('Void :n', ['n' => $doc->number]))">
        <form wire:submit="void" class="space-y-4">
            <p class="text-sm text-gray-600">{{ __('Voiding posts a reversing journal, so the books show both the original and its cancellation. Any payments matched to it stay on the account as credit.') }}</p>
            <x-form.textarea name="voidReason" :label="(__('Reason'))" wire:model="voidReason" rows="2" required />
            <div class="flex justify-end gap-2">
                <x-button variant="ghost" @click="$dispatch('close-modal', 'void-doc')">{{ __('Cancel') }}</x-button>
                <x-button type="submit" variant="danger">{{ __('Void it') }}</x-button>
            </div>
        </form>
    </x-modal>
</div>
