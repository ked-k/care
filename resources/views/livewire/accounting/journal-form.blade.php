<div>
    <x-page-header :title="(__('Journal entry'))" :subtitle="(__('For opening balances, accruals, corrections and anything without its own screen'))" icon="ik ik-book"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Journals' => route('accounts.journals'), 'New' => null]" />
    <form wire:submit="save"
          x-data="{ n(v) { return parseFloat(v) || 0 }, dr() { return ($wire.lines || []).reduce((s, l) => s + this.n(l.debit), 0) }, cr() { return ($wire.lines || []).reduce((s, l) => s + this.n(l.credit), 0) },
                    diff() { return Math.round((this.dr() - this.cr()) * 100) / 100 }, f(v) { return Number(v).toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) } }">
        <x-card class="mb-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <x-form.input type="date" name="form.date" :label="(__('Date'))" wire:model="form.date" required />
                <x-form.select name="form.type" :label="(__('Kind'))" wire:model="form.type">
                    <option value="manual">{{ __('Journal entry') }}</option>
                    <option value="opening">{{ __('Opening balances') }}</option>
                </x-form.select>
                <div class="md:col-span-2"><x-form.input name="form.reference" :label="(__('Reference'))" wire:model="form.reference" /></div>
                <div class="md:col-span-4"><x-form.input name="form.memo" :label="(__('What it’s for'))" wire:model="form.memo" required :placeholder="(__('e.g. Opening balances at 1 April 2026 from the audited accounts'))" /></div>
            </div>
        </x-card>
        <x-card no-padding>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-3 py-3 font-medium">{{ __('Account') }}</th>
                            <th class="px-3 py-3 font-medium">{{ __('Customer / supplier') }}</th>
                            <th class="px-3 py-3 font-medium">{{ __('Line description') }}</th>
                            <th class="w-32 px-3 py-3 text-right font-medium">{{ __('Debit') }}</th>
                            <th class="w-32 px-3 py-3 text-right font-medium">{{ __('Credit') }}</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($lines as $i => $l)
                            <tr wire:key="jl-{{ $i }}-{{ count($lines) }}">
                                <td class="px-3 py-2">
                                    <select wire:model.live="lines.{{ $i }}.account_id" @class(['w-full rounded-lg border bg-white py-2 pl-2.5 pr-7 text-sm outline-none focus:border-primary-500', 'border-accent-400' => $errors->has("lines.$i.account_id"), 'border-gray-200' => ! $errors->has("lines.$i.account_id")])>
                                        <option value="">{{ __('Choose…') }}</option>
                                        @foreach ($accountGroups as $type => $group)
                                            <optgroup label="{{ __(\App\Models\Accounting\Account::TYPES[$type]) }}">
                                                @foreach ($group as $a)<option value="{{ $a->id }}">{{ $a->code }} · {{ $a->name }}</option>@endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <select wire:model="lines.{{ $i }}.contact_id" @class(['w-full rounded-lg border bg-white py-2 pl-2.5 pr-7 text-sm outline-none focus:border-primary-500', 'border-accent-400' => $errors->has("lines.$i.contact_id"), 'border-gray-200' => ! $errors->has("lines.$i.contact_id")])>
                                        <option value="">{{ in_array($l['account_id'], $controls) ? __('Required…') : '—' }}</option>
                                        @foreach ($contacts as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2"><input type="text" wire:model="lines.{{ $i }}.description" class="w-full rounded-lg border border-gray-200 px-2.5 py-2 text-sm outline-none focus:border-primary-500"></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" min="0" wire:model="lines.{{ $i }}.debit" class="w-full rounded-lg border border-gray-200 px-2 py-2 text-right text-sm outline-none focus:border-primary-500"></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" min="0" wire:model="lines.{{ $i }}.credit" class="w-full rounded-lg border border-gray-200 px-2 py-2 text-right text-sm outline-none focus:border-primary-500"></td>
                                <td class="px-1 py-2 text-center"><button type="button" wire:click="removeLine({{ $i }})" class="text-gray-300 hover:text-accent-500"><i class="ik ik-x"></i></button></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-200 font-semibold text-gray-700">
                            <td colspan="3" class="px-3 py-3">
                                <span x-show="diff() === 0 && dr() > 0" class="text-green-600"><i class="ik ik-check-circle mr-1"></i>{{ __('Balanced') }}</span>
                                <span x-show="diff() !== 0" class="text-accent-600"><i class="ik ik-alert-circle mr-1"></i>{{ __('Out by') }} <span x-text="f(Math.abs(diff()))"></span></span>
                            </td>
                            <td class="px-3 py-3 text-right" x-text="f(dr())"></td>
                            <td class="px-3 py-3 text-right" x-text="f(cr())"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="flex items-center justify-between border-t border-gray-100 p-4">
                <x-button variant="secondary" size="sm" wire:click="addLine"><i class="ik ik-plus"></i>{{ __('Add line') }}</x-button>
                <div class="flex gap-2">
                    <x-button variant="ghost" :href="(route('accounts.journals'))" wire:navigate>{{ __('Cancel') }}</x-button>
                    <x-button type="submit" x-bind:disabled="diff() !== 0 || dr() === 0"><i class="ik ik-check"></i>{{ __('Post') }}</x-button>
                </div>
            </div>
        </x-card>
    </form>
</div>
