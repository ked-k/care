<div>
    @php $receipt = $kind === 'receipt'; @endphp
    <x-page-header :title="($title)" :subtitle="($receipt ? __('Money in from customers, matched to their invoices') : __('Money out to suppliers, matched to their bills'))"
                   :icon="'ik ik-'.($receipt ? 'download' : 'upload')" :breadcrumbs="['Finance' => route('accounts.dashboard'), $title => null]">
        <x-button wire:click="create"><i class="ik ik-plus"></i>{{ $receipt ? __('Record receipt') : __('Record payment') }}</x-button>
    </x-page-header>

    <x-card no-padding>
        <div class="border-b border-gray-100 p-4">
            <div class="relative max-w-md">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Number, reference or name…') }}"
                       class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Number') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Date') }}</th>
                        <th class="px-5 py-3 font-medium">{{ $receipt ? __('From') : __('To') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Account') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Amount') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Unmatched') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($payments as $p)
                        @php $free = round((float) $p->amount - (float) $p->allocations_sum_amount, 2); @endphp
                        <tr @class(['hover:bg-gray-50', 'opacity-50' => $p->isVoid()]) wire:key="pay-{{ $p->id }}">
                            <td class="px-5 py-3">
                                <a href="{{ route('accounts.payments.show', $p->id) }}" wire:navigate class="font-medium text-gray-700 hover:text-primary-600">{{ $p->number }}</a>
                                @if ($p->isVoid())<x-badge color="dark" class="ml-1">{{ __('Void') }}</x-badge>@endif
                                @if ($p->reference)<span class="block text-xs text-gray-400">{{ $p->reference }}</span>@endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-gray-500">{{ $p->date->format('d M Y') }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $p->contact?->name }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $p->bankAccount?->name }}<span class="block text-xs text-gray-400">{{ $p->methodLabel() }}</span></td>
                            <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-gray-700">{{ $cur->money($p->amount) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right {{ $free > 0 && ! $p->isVoid() ? 'font-medium text-amber-600' : 'text-gray-400' }}">{{ $free > 0 && ! $p->isVoid() ? $cur->money($free) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10"><x-empty-state :title="(__('Nothing recorded yet'))" :icon="'ik ik-'.($receipt ? 'download' : 'upload')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payments->hasPages())
            <div class="border-t border-gray-100 px-5 py-3">{{ $payments->links('pagination.radmin') }}</div>
        @endif
    </x-card>

    <x-drawer name="payment-form" :title="($receipt ? __('Record receipt') : __('Record payment'))" width="w-[34rem]">
        <form wire:submit="save" id="payment-form" class="space-y-4">
            <x-form.select name="form.contact_id" :label="($receipt ? __('Received from') : __('Paid to'))" wire:model.live="form.contact_id" required>
                <option value="">{{ __('Choose…') }}</option>
                @foreach ($contacts as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </x-form.select>
            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="number" step="0.01" name="form.amount" :label="(__('Amount'))" wire:model="form.amount" required />
                <x-form.input type="date" name="form.date" :label="(__('Date'))" wire:model="form.date" required />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-form.select name="form.bank_account_id" :label="($receipt ? __('Paid into') : __('Paid from'))" wire:model="form.bank_account_id" required>
                    @foreach ($banks as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                </x-form.select>
                <x-form.select name="form.method" :label="(__('How'))" wire:model="form.method">
                    @foreach (\App\Models\Accounting\Payment::METHODS as $k => $v)<option value="{{ $k }}">{{ __($v) }}</option>@endforeach
                </x-form.select>
            </div>
            <x-form.input name="form.reference" :label="(__('Reference'))" wire:model="form.reference" />

            @if ($form['contact_id'])
                <div class="rounded-lg border border-gray-100">
                    <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2">
                        <span class="text-sm font-medium text-gray-700">{{ $receipt ? __('Open invoices') : __('Open bills') }}</span>
                        @if ($openDocs->isNotEmpty())
                            <button type="button" wire:click="spread" class="text-xs font-medium text-primary-600 hover:underline">{{ __('Match oldest first') }}</button>
                        @endif
                    </div>
                    @forelse ($openDocs as $d)
                        <div class="flex items-center gap-3 border-b border-gray-50 px-3 py-2 text-sm" wire:key="al-{{ $d->id }}">
                            <div class="min-w-0 flex-1">
                                <span class="font-medium text-gray-700">{{ $d->number }}</span>
                                <span class="block text-xs {{ $d->isOverdue() ? 'text-accent-600' : 'text-gray-400' }}">{{ __('due :d', ['d' => $d->due_date?->format('d M Y') ?? '—']) }} · {{ __('owing :b', ['b' => $cur->money($d->balance())]) }}</span>
                            </div>
                            <input type="number" step="0.01" min="0" wire:model="alloc.{{ $d->id }}" placeholder="0.00"
                                   class="w-28 rounded-lg border border-gray-200 px-2 py-1.5 text-right text-sm outline-none focus:border-primary-500">
                        </div>
                    @empty
                        <p class="px-3 py-3 text-sm text-gray-400">{{ __('Nothing open — the whole amount will sit on their account as credit.') }}</p>
                    @endforelse
                </div>
            @endif
            <x-form.textarea name="form.notes" :label="(__('Notes'))" wire:model="form.notes" rows="2" />
        </form>
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'payment-form')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="payment-form">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
