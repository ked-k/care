<div>
    @php
        $back = match ($kind) { 'bill' => route('accounts.bills'), 'invoice' => route('accounts.invoices'), default => route('accounts.money') };
        $listName = match ($kind) { 'bill' => 'Supplier bills', 'invoice' => 'Sales invoices', default => 'Money in / out' };
        $whoLabel = match ($kind) { 'invoice' => __('Customer'), 'bill' => __('Supplier'), 'money_in' => __('Received from'), default => __('Paid to') };
        $decimals = (int) $cur->currency_decimals;
    @endphp
    <x-page-header :title="($documentId ? __('Edit draft :n', ['n' => $number]) : __('New :k', ['k' => mb_strtolower(__($label))]))"
                   :subtitle="($money ? ($kind === 'money_out' ? __('Money that has already left a bank or cash account — an expense, a purchase, a fee') : __('Money that has already come in — a grant, a refund, interest, a cash sale')) : __('Number :n', ['n' => $number]))"
                   :icon="(\App\Models\Accounting\Document::KINDS[$kind][2])"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), $listName => $back, ($documentId ? $number : __('New')) => null]" />

    <form wire:submit="save(false)"
          x-data="{
              dp: {{ $decimals }},
              fmt(n) { return @js($cur->currency_symbol) + (/^[A-Za-z]+$/.test(@js($cur->currency_symbol)) ? ' ' : '') + Number(n || 0).toLocaleString('en-GB', { minimumFractionDigits: this.dp, maximumFractionDigits: this.dp }) },
              r(n) { return Math.round((n + Number.EPSILON) * 100) / 100 },
              line(i) {
                  const l = ($wire.lines || [])[i] || {};
                  const gross = this.r((parseFloat(l.qty) || 0) * (parseFloat(l.unit_price) || 0) - (parseFloat(l.discount) || 0));
                  const rate = parseFloat(l.vat_rate) || 0;
                  if ($wire.form.prices_include_vat) { const net = rate ? this.r(gross / (1 + rate / 100)) : gross; return { net, vat: this.r(gross - net), total: gross } }
                  const vat = this.r(gross * rate / 100); return { net: gross, vat, total: this.r(gross + vat) };
              },
              sum(k) { return ($wire.lines || []).reduce((s, l, i) => s + this.line(i)[k], 0) },
          }">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
            <div class="space-y-6 xl:col-span-3">
                <x-card>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="lg:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ $whoLabel }}@unless ($money)<span class="text-accent-500">*</span>@endunless</label>
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <x-form.select name="form.contact_id" wire:model.live="form.contact_id">
                                        <option value="">{{ $money ? __('— nobody in particular —') : __('Choose…') }}</option>
                                        @foreach ($contacts as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </x-form.select>
                                </div>
                                <x-button variant="secondary" @click="$dispatch('open-modal', 'new-contact')" :title="(__('Add a new one'))"><i class="ik ik-plus"></i></x-button>
                            </div>
                        </div>
                        @if ($money)
                            <div class="lg:col-span-2">
                                <x-form.select name="form.bank_account_id" :label="($kind === 'money_out' ? __('Paid from') : __('Paid into'))" wire:model="form.bank_account_id" required>
                                    <option value="">{{ __('Choose…') }}</option>
                                    @foreach ($banks as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </x-form.select>
                            </div>
                        @endif
                        <x-form.input type="date" name="form.issue_date" :label="($money ? __('Date') : __('Issue date'))" wire:model="form.issue_date" required />
                        @unless ($money)
                            <x-form.input type="date" name="form.due_date" :label="(__('Due date'))" wire:model="form.due_date" />
                        @endunless
                        <div class="{{ $money ? 'lg:col-span-3' : 'lg:col-span-2' }}">
                            <x-form.input name="form.reference" :label="($kind === 'bill' ? __('Supplier’s invoice number') : __('Reference'))" wire:model="form.reference"
                                          :placeholder="($kind === 'invoice' ? __('PO number, local authority ref…') : '')" />
                        </div>
                    </div>
                </x-card>

                <x-card no-padding>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                                    <th class="px-3 py-3 font-medium">{{ __('Description and account') }}</th>
                                    <th class="w-20 px-3 py-3 text-right font-medium">{{ __('Qty') }}</th>
                                    <th class="w-28 px-3 py-3 text-right font-medium">{{ __('Price') }}</th>
                                    <th class="w-24 px-3 py-3 text-right font-medium">{{ __('Discount') }}</th>
                                    <th class="w-20 px-3 py-3 text-right font-medium">{{ __('VAT %') }}</th>
                                    <th class="w-28 px-3 py-3 text-right font-medium">{{ __('Amount') }}</th>
                                    <th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($lines as $i => $line)
                                    <tr wire:key="line-{{ $i }}-{{ count($lines) }}" class="align-top">
                                        <td class="px-3 py-2">
                                            <textarea wire:model="lines.{{ $i }}.description" rows="1" style="min-width:16rem" placeholder="{{ __('What it’s for') }}"
                                                      class="w-full rounded-lg border border-gray-200 px-2.5 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100"></textarea>
                                            <select wire:model="lines.{{ $i }}.account_id" @class(['mt-1.5 w-full rounded-lg border bg-white py-1.5 pl-2.5 pr-7 text-xs text-gray-600 outline-none focus:border-primary-500', 'border-accent-400' => $errors->has("lines.$i.account_id"), 'border-gray-200' => ! $errors->has("lines.$i.account_id")])>
                                                <option value="">{{ __('Choose account…') }}</option>
                                                @foreach ($accountGroups as $type => $group)
                                                    <optgroup label="{{ __(\App\Models\Accounting\Account::TYPES[$type]) }}">
                                                        @foreach ($group as $a)
                                                            <option value="{{ $a->id }}">{{ $a->code }} · {{ $a->name }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td class="px-3 py-2"><input type="number" step="any" wire:model="lines.{{ $i }}.qty" class="w-full rounded-lg border border-gray-200 px-2 py-2 text-right text-sm outline-none focus:border-primary-500"></td>
                                        <td class="px-3 py-2"><input type="number" step="any" wire:model="lines.{{ $i }}.unit_price" class="w-full rounded-lg border border-gray-200 px-2 py-2 text-right text-sm outline-none focus:border-primary-500"></td>
                                        <td class="px-3 py-2"><input type="number" step="any" min="0" wire:model="lines.{{ $i }}.discount" class="w-full rounded-lg border border-gray-200 px-2 py-2 text-right text-sm outline-none focus:border-primary-500"></td>
                                        <td class="px-3 py-2"><input type="number" step="any" min="0" max="100" wire:model="lines.{{ $i }}.vat_rate" class="w-full rounded-lg border border-gray-200 px-2 py-2 text-right text-sm outline-none focus:border-primary-500"></td>
                                        <td class="whitespace-nowrap px-3 py-2 pt-4 text-right font-semibold text-gray-700" x-text="fmt(line({{ $i }}).total)"></td>
                                        <td class="px-1 py-2 pt-3 text-center">
                                            <button type="button" wire:click="removeLine({{ $i }})" class="text-gray-300 hover:text-accent-500" title="{{ __('Remove line') }}"><i class="ik ik-x"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @error('lines')<p class="px-4 pt-2 text-xs text-accent-600">{{ $message }}</p>@enderror
                    <div class="flex flex-col gap-4 border-t border-gray-100 p-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-3">
                            <x-button variant="secondary" size="sm" wire:click="addLine"><i class="ik ik-plus"></i>{{ __('Add line') }}</x-button>
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" wire:model.live="form.prices_include_vat" class="rounded border-gray-300 text-primary-600">
                                {{ __('Prices already include VAT') }}
                            </label>
                        </div>
                        <dl class="w-full max-w-xs space-y-1.5 text-sm">
                            <div class="flex justify-between text-gray-500"><dt>{{ __('Subtotal') }}</dt><dd x-text="fmt(sum('net'))"></dd></div>
                            <div class="flex justify-between text-gray-500"><dt>{{ __('VAT') }}</dt><dd x-text="fmt(sum('vat'))"></dd></div>
                            <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-semibold text-gray-800"><dt>{{ __('Total') }}</dt><dd x-text="fmt(sum('total'))"></dd></div>
                        </dl>
                    </div>
                </x-card>

                <x-card>
                    <x-form.textarea name="form.notes" :label="($kind === 'invoice' ? __('Notes on the invoice') : __('Notes'))" wire:model="form.notes" rows="3"
                                     :placeholder="($kind === 'invoice' ? __('Payment details, thank-you note…') : '')" />
                </x-card>
            </div>

            <div class="space-y-4">
                <x-card>
                    <div class="space-y-3">
                        <x-button type="button" class="w-full justify-center" wire:click="save(true)" wire:loading.attr="disabled">
                            <i class="ik ik-check"></i>{{ $money ? __('Save and post') : __('Save and approve') }}
                        </x-button>
                        <x-button type="submit" variant="secondary" class="w-full justify-center" wire:loading.attr="disabled">{{ __('Save as draft') }}</x-button>
                        <x-button variant="ghost" class="w-full justify-center" :href="($documentId ? route('accounts.documents.show', $documentId) : $back)" wire:navigate>{{ __('Cancel') }}</x-button>
                    </div>
                    <p class="mt-4 text-xs leading-relaxed text-gray-400">
                        {{ $money ? __('Posting puts it straight into the books and the bank account’s balance.') : __('Approving posts it to the books and it can then be paid. After that it can’t be edited — only voided and replaced.') }}
                    </p>
                </x-card>
            </div>
        </div>
    </form>

    <x-modal name="new-contact" :title="(in_array($kind, ['invoice', 'money_in']) ? __('New customer') : __('New supplier'))">
        <form wire:submit="addContact" class="space-y-4">
            <x-form.input name="newContact.name" :label="(__('Name'))" wire:model="newContact.name" required />
            <x-form.input type="email" name="newContact.email" :label="(__('Email'))" wire:model="newContact.email" />
            <p class="text-xs text-gray-400">{{ __('Address, phone and payment terms can be added later under Customers / Suppliers.') }}</p>
            <div class="flex justify-end gap-2">
                <x-button variant="ghost" @click="$dispatch('close-modal', 'new-contact')">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Add') }}</x-button>
            </div>
        </form>
    </x-modal>
</div>
