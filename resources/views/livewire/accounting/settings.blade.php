<div>
    <x-page-header :title="(__('Accounting settings'))" :subtitle="(__('Set once per agency — each agency keeps its own books, currency and year'))" icon="ik ik-settings"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Settings' => null]">
        <x-button wire:click="save" wire:loading.attr="disabled"><i class="ik ik-check"></i>{{ __('Save settings') }}</x-button>
    </x-page-header>

    <form wire:submit="save" class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-card :title="(__('Organisation'))" :subtitle="(__('Shown on invoices and the financial statements'))">
            <div class="space-y-4">
                <x-form.input name="form.legal_name" :label="(__('Legal name'))" wire:model="form.legal_name" />
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <x-form.input name="form.registration_number" :label="(__('Registration number'))" wire:model="form.registration_number" />
                    <x-form.input name="form.tax_number" :label="(__('VAT / TIN number'))" wire:model="form.tax_number" />
                </div>
                <x-form.textarea name="form.address" :label="(__('Registered address'))" wire:model="form.address" rows="2" />
                <x-form.select name="form.organisation_type" :label="(__('Type of organisation'))" wire:model.live="form.organisation_type"
                               :hint="(__('Changes the wording on statements — e.g. a co-operative reports a “Surplus” on a “Statement of financial position”.'))">
                    @foreach (\App\Models\Accounting\AccountingSetting::ORGANISATION_TYPES as $k => $v)<option value="{{ $k }}">{{ __($v) }}</option>@endforeach
                </x-form.select>
            </div>
        </x-card>

        <x-card :title="(__('Currency and financial year'))">
            <div class="space-y-4">
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Currency') }}</label>
                        <input list="currency-codes" wire:model.live.blur="form.currency_code" maxlength="3"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm uppercase outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                        <datalist id="currency-codes">@foreach (\App\Models\Accounting\AccountingSetting::CURRENCIES as $c => $sym)<option value="{{ $c }}">{{ $sym }}</option>@endforeach</datalist>
                        @error('form.currency_code')<p class="mt-1 text-xs text-accent-600">{{ $message }}</p>@enderror
                    </div>
                    <x-form.input name="form.currency_symbol" :label="(__('Symbol'))" wire:model="form.currency_symbol" />
                    <x-form.input type="number" name="form.currency_decimals" :label="(__('Decimals'))" wire:model="form.currency_decimals" min="0" max="3" />
                </div>
                @if ($hasJournals)
                    <p class="text-xs text-amber-600"><i class="ik ik-alert-triangle"></i> {{ __('Entries are already posted. Changing the currency only changes how amounts are shown — nothing is converted.') }}</p>
                @endif
                <div class="grid grid-cols-2 gap-3">
                    <x-form.select name="form.fy_start_month" :label="(__('Year starts — month'))" wire:model="form.fy_start_month">
                        @foreach ($months as $m => $name)<option value="{{ $m }}">{{ __($name) }}</option>@endforeach
                    </x-form.select>
                    <x-form.input type="number" name="form.fy_start_day" :label="(__('Day'))" wire:model="form.fy_start_day" min="1" max="28" />
                </div>
                <p class="text-xs text-gray-400">{{ __('UK companies often use 1 April; Uganda co-operatives and many charities use 1 January.') }}</p>
            </div>
        </x-card>

        <x-card :title="(__('Invoices, VAT and numbering'))">
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <x-form.input type="number" name="form.payment_terms_days" :label="(__('Payment terms (days)'))" wire:model="form.payment_terms_days" />
                    <x-form.input type="number" step="0.01" name="form.default_vat_rate" :label="(__('Default VAT %'))" wire:model="form.default_vat_rate" />
                    <div class="flex items-end pb-2.5"><x-form.checkbox name="form.vat_registered" :label="(__('VAT registered'))" wire:model="form.vat_registered" /></div>
                </div>
                <div class="overflow-hidden rounded-lg border border-gray-100">
                    <table class="w-full text-sm">
                        <thead><tr class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-400"><th class="px-3 py-2 font-medium">{{ __('Record') }}</th><th class="px-3 py-2 font-medium">{{ __('Prefix') }}</th><th class="px-3 py-2 font-medium">{{ __('Next number') }}</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach (['invoice' => 'Sales invoices', 'bill' => 'Supplier bills', 'receipt' => 'Receipts', 'payment' => 'Supplier payments', 'money_in' => 'Money in', 'money_out' => 'Money out', 'journal' => 'Journals'] as $k => $label)
                                <tr>
                                    <td class="px-3 py-1.5 text-gray-600">{{ __($label) }}</td>
                                    <td class="px-3 py-1.5"><input type="text" wire:model="numbering.{{ $k }}.prefix" class="w-24 rounded border border-gray-200 px-2 py-1 font-mono text-xs outline-none focus:border-primary-500"></td>
                                    <td class="px-3 py-1.5"><input type="number" min="1" wire:model="numbering.{{ $k }}.next" class="w-24 rounded border border-gray-200 px-2 py-1 text-xs outline-none focus:border-primary-500"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-form.textarea name="form.invoice_footer" :label="(__('Invoice footer'))" wire:model="form.invoice_footer" rows="2" :placeholder="(__('Bank details for payment, company number…'))" />
            </div>
        </x-card>

        <x-card :title="(__('Automatic posting'))" :subtitle="(__('These keep working where they are and post into the books by themselves'))">
            <div class="space-y-3">
                @foreach (['care_invoices' => [__('Care invoicing'), __('Issued care invoices become sales invoices; “mark as paid” records the receipt.')],
                           'payroll' => [__('Payroll'), __('Approved runs post wages, PAYE, pension and net pay owed; paid runs post the bank payment.')],
                           'shop_sales' => [__('Shop sales'), __('Paid orders post the sale, delivery and VAT; refunds reverse it.')],
                           'shop_stock' => [__('Shop stock'), __('Deliveries become supplier bills; sales post their cost (from the actual batches sold); write-offs post to stock adjustments.')]] as $k => [$t, $d])
                    <label class="flex items-start gap-3 rounded-lg border border-gray-100 p-3">
                        <input type="checkbox" wire:model="auto.{{ $k }}" class="mt-0.5 rounded border-gray-300 text-primary-600">
                        <span><span class="block text-sm font-medium text-gray-700">{{ $t }}</span><span class="block text-xs text-gray-400">{{ $d }}</span></span>
                    </label>
                @endforeach
                <div class="rounded-lg bg-gray-50 p-3">
                    <x-form.input type="date" name="form.posting_from" :label="(__('Post records dated from'))" wire:model="form.posting_from"
                                  :hint="(__('Anything older belongs in your opening balances (post them as a journal entry).'))" />
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <x-button type="button" variant="secondary" size="sm" wire:click="postPast" wire:loading.attr="disabled" wire:target="postPast">
                            <i class="ik ik-refresh-cw"></i>{{ __('Save and post past records now') }}
                        </x-button>
                        <span wire:loading wire:target="postPast" class="text-xs text-gray-400">{{ __('Posting…') }}</span>
                    </div>
                    @if ($backfill)
                        <ul class="mt-3 space-y-0.5 text-xs text-gray-500">
                            @foreach ($backfill as $what => $r)
                                <li>{{ __($what) }}: {{ $r['checked'] }} {{ __('checked') }}@if ($r['failed'])<span class="text-accent-600"> · {{ $r['failed'] }} {{ __('couldn’t post') }}</span>@endif</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </x-card>

        <x-card :title="(__('Default accounts'))" :subtitle="(__('Which account each automatic job posts to'))" class="xl:col-span-2">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach (\App\Services\Accounting\DefaultChart::SYSTEM_LABELS as $key => $label)
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500">{{ __($label) }}</label>
                        <select wire:model="mapping.{{ $key }}" class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-2.5 pr-7 text-sm outline-none focus:border-primary-500">
                            @foreach ($accountsByType[$typeOf($key)] ?? [] as $a)<option value="{{ $a->id }}">{{ $a->code }} · {{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card :title="(__('Year-end appropriations'))" :subtitle="(__('Share-outs of the year’s result into reserves — used when a year is closed (next update)'))" class="xl:col-span-2">
            <div class="space-y-3">
                @forelse ($appropriations as $i => $a)
                    <div class="grid grid-cols-1 items-end gap-3 rounded-lg border border-gray-100 p-3 md:grid-cols-12" wire:key="ap-{{ $i }}">
                        <div class="md:col-span-3"><x-form.input name="appropriations.{{ $i }}.name" :label="(__('Name'))" wire:model="appropriations.{{ $i }}.name" /></div>
                        <div class="md:col-span-4">
                            <x-form.select name="appropriations.{{ $i }}.account_id" :label="(__('Into account'))" wire:model="appropriations.{{ $i }}.account_id">
                                <option value="">{{ __('Choose…') }}</option>
                                @foreach ($equity as $e)<option value="{{ $e->id }}">{{ $e->code }} · {{ $e->name }}</option>@endforeach
                            </x-form.select>
                        </div>
                        <div class="md:col-span-3">
                            <x-form.select name="appropriations.{{ $i }}.basis" :label="(__('Worked out as'))" wire:model="appropriations.{{ $i }}.basis">
                                <option value="surplus">{{ __('% of the surplus') }}</option>
                                <option value="turnover">{{ __('% of turnover') }}</option>
                                <option value="fixed">{{ __('A fixed amount') }}</option>
                            </x-form.select>
                        </div>
                        <div class="md:col-span-1"><x-form.input type="number" step="0.01" name="appropriations.{{ $i }}.rate" :label="(__('Rate'))" wire:model="appropriations.{{ $i }}.rate" /></div>
                        <div class="md:col-span-1 pb-2 text-right"><button type="button" wire:click="removeAppropriation({{ $i }})" class="text-gray-400 hover:text-accent-500"><i class="ik ik-trash-2"></i></button></div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">{{ __('None — the whole result goes to retained earnings. Co-operatives usually set aside a statutory reserve and other funds.') }}</p>
                @endforelse
                <div class="flex flex-wrap gap-2">
                    <x-button type="button" variant="secondary" size="sm" wire:click="addAppropriation"><i class="ik ik-plus"></i>{{ __('Add') }}</x-button>
                    <x-button type="button" variant="ghost" size="sm" wire:click="cooperativeTemplate"
                              wire:confirm="{{ __('Fill in the usual co-operative appropriations (statutory reserve 10%, development fund 5%, education fund 1% of turnover) and add the reserve accounts?') }}">{{ __('Use the co-operative template') }}</x-button>
                </div>
            </div>
        </x-card>

        <div class="xl:col-span-2 flex justify-end">
            <x-button type="submit"><i class="ik ik-check"></i>{{ __('Save settings') }}</x-button>
        </div>
    </form>
</div>
