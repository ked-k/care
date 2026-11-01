<div>
    @php $receipt = $p->kind === 'receipt'; $listName = $receipt ? 'Receipts' : 'Supplier payments'; @endphp
    <x-page-header :title="($p->kindLabel()).' '.($p->number)" :subtitle="($p->contact?->name).' · '.($p->date->format('d M Y'))"
                   :icon="'ik ik-'.($receipt ? 'download' : 'upload')"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), $listName => route($receipt ? 'accounts.receipts' : 'accounts.payments'), $p->number => null]">
        @if ($p->isVoid())<x-badge color="dark" class="px-3 py-1 text-sm">{{ __('Void') }}</x-badge>@endif
        <x-button variant="secondary" :href="(route('accounts.payments.print', $p->id))" target="_blank"><i class="ik ik-printer"></i>{{ $receipt ? __('Print receipt') : __('Print remittance') }}</x-button>
        @unless ($p->isVoid())
            <x-button variant="ghost" @click="$dispatch('open-modal', 'void-pay')"><i class="ik ik-slash"></i>{{ __('Void') }}</x-button>
        @endunless
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-card>
                <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div><dt class="text-gray-400">{{ __('Amount') }}</dt><dd class="text-lg font-semibold text-gray-800">{{ $cur->money($p->amount) }}</dd></div>
                    <div><dt class="text-gray-400">{{ $receipt ? __('Paid into') : __('Paid from') }}</dt><dd class="font-medium text-gray-700">{{ $p->bankAccount?->name }}</dd></div>
                    <div><dt class="text-gray-400">{{ __('How') }}</dt><dd class="font-medium text-gray-700">{{ $p->methodLabel() }}</dd></div>
                    <div><dt class="text-gray-400">{{ __('Reference') }}</dt><dd class="font-medium text-gray-700">{{ $p->reference ?: '—' }}</dd></div>
                </dl>
                @if ($p->notes)<p class="mt-4 whitespace-pre-line border-t border-gray-100 pt-3 text-sm text-gray-500">{{ $p->notes }}</p>@endif
            </x-card>

            <x-card no-padding :title="(__('What it paid'))">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($p->allocations as $a)
                            <tr>
                                <td class="px-5 py-3"><a href="{{ route('accounts.documents.show', $a->document_id) }}" wire:navigate class="font-medium text-gray-700 hover:text-primary-600">{{ $a->document->number }}</a>
                                    <span class="block text-xs text-gray-400">{{ $a->document->issue_date->format('d M Y') }} · {{ __('total :t', ['t' => $cur->money($a->document->total)]) }}</span></td>
                                <td class="px-5 py-3"><x-badge :color="($a->document->statusColor())">{{ $a->document->statusLabel() }}</x-badge></td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-700">{{ $cur->money($a->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-5 py-6 text-center text-gray-400">{{ __('Not matched to anything yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if ($p->unallocated() > 0 && ! $p->isVoid())
                        <tfoot><tr class="border-t border-gray-100 bg-amber-500/10"><td colspan="2" class="px-5 py-3 text-amber-700">{{ __('Unmatched — on the account as credit') }}</td><td class="px-5 py-3 text-right font-semibold text-amber-700">{{ $cur->money($p->unallocated()) }}</td></tr></tfoot>
                    @endif
                </table>
            </x-card>

            @if ($open->isNotEmpty())
                <x-card :title="(__('Match the rest'))" :subtitle="(__(':a left to match', ['a' => $cur->money($p->unallocated())]))">
                    <form wire:submit="allocate" class="space-y-2">
                        @foreach ($open as $d)
                            <div class="flex items-center gap-3 text-sm" wire:key="o-{{ $d->id }}">
                                <span class="flex-1"><span class="font-medium text-gray-700">{{ $d->number }}</span> <span class="text-gray-400">· {{ __('owing :b', ['b' => $cur->money($d->balance())]) }}</span></span>
                                <input type="number" step="0.01" min="0" wire:model="alloc.{{ $d->id }}" placeholder="0.00" class="w-28 rounded-lg border border-gray-200 px-2 py-1.5 text-right text-sm outline-none focus:border-primary-500">
                            </div>
                        @endforeach
                        <div class="pt-2 text-right"><x-button type="submit" size="sm">{{ __('Match') }}</x-button></div>
                    </form>
                </x-card>
            @endif
        </div>
        <div>
            <x-card :title="(__('In the books'))">
                <ul class="space-y-2 text-sm text-gray-500">
                    <li>{{ __('Recorded :d', ['d' => $p->created_at->format('d M Y H:i')]) }}{{ $p->creator ? ' · '.$p->creator->name : '' }}</li>
                    @if ($p->journal)<li>{{ __('Journal') }} <a href="{{ route('accounts.journals.show', $p->journal_id) }}" wire:navigate class="text-primary-600 hover:underline">{{ $p->journal->number }}</a></li>@endif
                    @if ($p->contact)<li><a href="{{ route('accounts.contacts.show', $p->contact_id) }}" wire:navigate class="text-primary-600 hover:underline">{{ __(':n’s statement', ['n' => $p->contact->name]) }}</a></li>@endif
                </ul>
            </x-card>
        </div>
    </div>

    <x-modal name="void-pay" :title="(__('Void :n', ['n' => $p->number]))">
        <form wire:submit="void" class="space-y-4">
            <p class="text-sm text-gray-600">{{ __('Use this if the payment was entered by mistake or bounced. A reversing journal is posted and the invoices/bills it paid become unpaid again.') }}</p>
            <x-form.textarea name="voidReason" :label="(__('Reason'))" wire:model="voidReason" rows="2" required />
            <div class="flex justify-end gap-2">
                <x-button variant="ghost" @click="$dispatch('close-modal', 'void-pay')">{{ __('Cancel') }}</x-button>
                <x-button type="submit" variant="danger">{{ __('Void it') }}</x-button>
            </div>
        </form>
    </x-modal>
</div>
