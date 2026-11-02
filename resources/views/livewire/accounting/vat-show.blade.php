<div>
    <x-page-header :title="__('VAT return :a – :b', ['a' => $ret->period_start->format('j M Y'), 'b' => $ret->period_end->format('j M Y')])"
                   :subtitle="__('Filed :d', ['d' => $ret->filed_at?->format('d M Y H:i')]).($ret->filer ? ' · '.$ret->filer->name : '').($ret->reference ? ' · '.$ret->reference : '')" icon="ik ik-percent"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'VAT' => route('accounts.vat'), $ret->label() => null]">
        @if ($ret->isPaid())
            <x-badge color="success" class="px-3 py-1 text-sm">{{ $ret->net() < 0 ? __('Refunded') : (abs($ret->net()) < 0.005 ? __('Nothing to pay') : __('Paid')) }}</x-badge>
        @else
            <x-button @click="$dispatch('open-drawer', 'vat-pay')"><i class="ik ik-{{ $ret->net() < 0 ? 'download' : 'upload' }}"></i>{{ $ret->net() < 0 ? __('Record refund') : __('Record payment') }}</x-button>
        @endif
        <x-button variant="secondary" :href="route('accounts.vat.print', ['return' => $ret->id])" target="_blank"><i class="ik ik-printer"></i>{{ __('Print') }}</x-button>
        @if ($canReopen)
            <x-button variant="secondary" wire:click="reopen" wire:confirm="{{ __('Re-open this return? Its entries go back to being unfiled, so the period can be checked and filed again.') }}"><i class="ik ik-rotate-ccw"></i>{{ __('Re-open') }}</x-button>
        @endif
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-card :title="__('As filed')">
                @include('accounting.vat._boxes')
                @if ($ret->notes)<p class="mt-4 whitespace-pre-line text-sm text-gray-500">{{ $ret->notes }}</p>@endif
            </x-card>
            <x-card no-padding :title="trans_choice(':n entry|:n entries', $calc['rows']->count(), ['n' => $calc['rows']->count()])">
                <div class="max-h-[32rem] overflow-y-auto px-2 pb-2">@include('accounting.vat._rows', ['rows' => $calc['rows'], 'links' => true])</div>
            </x-card>
        </div>
        <div class="space-y-6 xl:col-span-1">
            <x-card :title="__('Payment')">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">{{ $ret->net() < 0 ? __('Refund due') : __('Amount to pay') }}</dt><dd class="font-semibold">{{ $cur->money(abs($ret->net())) }}</dd></div>
                    @if ($ret->due_date)<div class="flex justify-between"><dt class="text-gray-500">{{ __('Due by') }}</dt><dd>{{ $ret->due_date->format('j M Y') }}</dd></div>@endif
                    @if ($ret->paymentJournal)
                        <div class="flex justify-between"><dt class="text-gray-500">{{ __('Paid on') }}</dt><dd>{{ $ret->paid_on?->format('j M Y') }} · <a href="{{ route('accounts.journals.show', $ret->payment_journal_id) }}" wire:navigate class="text-primary-600 hover:underline">{{ $ret->paymentJournal->number }}</a></dd></div>
                    @endif
                </dl>
                @if ($ret->paymentJournal)
                    <button type="button" wire:click="undoPayment" wire:confirm="{{ __('Reverse the payment journal?') }}" class="mt-3 text-xs text-gray-400 hover:underline">{{ __('Undo payment') }}</button>
                @endif
            </x-card>
        </div>
    </div>

    <x-drawer name="vat-pay" :title="$ret->net() < 0 ? __('Record VAT refund') : __('Record VAT payment')" width="w-[26rem]">
        <form wire:submit="recordPayment" id="vat-pay" class="space-y-4">
            <p class="text-sm text-gray-500">{{ $cur->money(abs($ret->net())) }} · {{ $ret->label() }}</p>
            <x-form.select name="pay.bank_account_id" :label="$ret->net() < 0 ? __('Received into') : __('Paid from')" wire:model="pay.bank_account_id">
                @foreach ($banks as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
            </x-form.select>
            <x-form.input type="date" name="pay.date" :label="__('Date')" wire:model="pay.date" />
            <p class="text-xs text-gray-400">{{ __('Posts Dr VAT / Cr bank (the other way for a refund), clearing the VAT owed for this return.') }}</p>
        </form>
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'vat-pay')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="vat-pay">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
