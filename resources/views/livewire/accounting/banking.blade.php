<div>
    <x-page-header :title="(__('Bank & cash'))" :subtitle="(__('Every account money goes through — click one for its full register'))" icon="ik ik-credit-card"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Bank & cash' => null]">
        <x-button variant="secondary" @click="$dispatch('open-modal', 'transfer')"><i class="ik ik-repeat"></i>{{ __('Transfer') }}</x-button>
        <x-button wire:click="create"><i class="ik ik-plus"></i>{{ __('Add account') }}</x-button>
    </x-page-header>

    @if (request('transfer'))<div x-init="$nextTick(() => $dispatch('open-modal', 'transfer'))"></div>@endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($accounts as $row)
            @php $a = $row['account']; @endphp
            <x-card wire:key="bank-{{ $a->id }}" :class="$a->is_active ? '' : 'opacity-60'">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-500/10 text-primary-600">
                            <i class="ik ik-{{ $a->subtype === 'cash' ? 'dollar-sign' : ($a->subtype === 'mobile_money' ? 'smartphone' : 'credit-card') }}"></i>
                        </span>
                        <div>
                            <h3 class="font-semibold text-gray-800">{{ $a->name }}</h3>
                            <p class="text-xs text-gray-400">{{ $a->code }} · {{ $a->subtypeLabel() }}{{ $a->bank_name ? ' · '.$a->bank_name : '' }}{{ $a->account_number ? ' · '.$a->account_number : '' }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="edit('{{ $a->id }}')" class="text-gray-400 hover:text-primary-600" title="{{ __('Edit') }}"><i class="ik ik-edit-2"></i></button>
                </div>
                <p @class(['mt-4 text-2xl font-bold', 'text-accent-600' => $row['balance'] < 0, 'text-gray-800' => $row['balance'] >= 0])>{{ $cur->money($row['balance']) }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ __('This month: :in in · :out out', ['in' => $cur->money($row['in']), 'out' => $cur->money($row['out'])]) }}</p>
                <div class="mt-4 flex gap-3 border-t border-gray-100 pt-3 text-sm">
                    <a href="{{ route('accounts.reports.show', ['report' => 'ledger', 'account' => $a->id]) }}" wire:navigate class="font-medium text-primary-600 hover:underline">{{ __('Register') }}</a>
                    <a href="{{ route('accounts.documents.create', 'money_out') }}" wire:navigate class="text-gray-500 hover:underline">{{ __('Spend') }}</a>
                    <a href="{{ route('accounts.documents.create', 'money_in') }}" wire:navigate class="text-gray-500 hover:underline">{{ __('Receive') }}</a>
                </div>
            </x-card>
        @endforeach
    </div>

    <x-drawer name="bank-form" :title="($editingId ? __('Edit account') : __('Add bank or cash account'))" width="w-[28rem]">
        <form wire:submit="save" id="bank-form" class="space-y-4">
            <x-form.input name="form.name" :label="(__('Name'))" wire:model="form.name" required :placeholder="(__('e.g. Barclays Business, Stanbic, MTN MoMo'))" />
            <div class="grid grid-cols-2 gap-3">
                <x-form.select name="form.subtype" :label="(__('Type'))" wire:model="form.subtype">
                    <option value="bank">{{ __('Bank') }}</option><option value="cash">{{ __('Cash') }}</option><option value="mobile_money">{{ __('Mobile money') }}</option>
                </x-form.select>
                <x-form.input name="form.code" :label="(__('Account code'))" wire:model="form.code" required />
            </div>
            <x-form.input name="form.bank_name" :label="(__('Bank / provider'))" wire:model="form.bank_name" />
            <div class="grid grid-cols-2 gap-3">
                <x-form.input name="form.account_number" :label="(__('Account number'))" wire:model="form.account_number" />
                <x-form.input name="form.sort_code" :label="(__('Sort code / branch'))" wire:model="form.sort_code" />
            </div>
            <x-form.textarea name="form.description" :label="(__('Notes'))" wire:model="form.description" rows="2" />
            <x-form.checkbox name="form.is_active" :label="(__('In use'))" wire:model="form.is_active" />
            <p class="text-xs text-gray-400">{{ __('To give a new account its starting balance, post a journal entry: debit this account, credit Opening balances.') }}</p>
        </form>
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'bank-form')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="bank-form">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-modal name="transfer" :title="(__('Move money between accounts'))">
        <form wire:submit="saveTransfer" class="space-y-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-form.select name="transfer.from" :label="(__('From'))" wire:model="transfer.from" required>
                    <option value="">{{ __('Choose…') }}</option>
                    @foreach ($active as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                </x-form.select>
                <x-form.select name="transfer.to" :label="(__('To'))" wire:model="transfer.to" required>
                    <option value="">{{ __('Choose…') }}</option>
                    @foreach ($active as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                </x-form.select>
                <x-form.input type="number" step="0.01" name="transfer.amount" :label="(__('Amount'))" wire:model="transfer.amount" required />
                <x-form.input type="date" name="transfer.date" :label="(__('Date'))" wire:model="transfer.date" required />
            </div>
            <x-form.input name="transfer.reference" :label="(__('Reference'))" wire:model="transfer.reference" :placeholder="(__('e.g. Card takings paid out by Stripe'))" />
            <div class="flex justify-end gap-2">
                <x-button variant="ghost" @click="$dispatch('close-modal', 'transfer')">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Post transfer') }}</x-button>
            </div>
        </form>
    </x-modal>
</div>
