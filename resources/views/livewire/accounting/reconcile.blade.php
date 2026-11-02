<div>
    <x-page-header :title="__('Reconcile :a', ['a' => $account->name])"
                   :subtitle="$last ? __('Last reconciled to :d (balance :b)', ['d' => $last->statement_date->format('d M Y'), 'b' => $cur->money($last->statement_balance)]) : __('Not reconciled yet')"
                   icon="ik ik-check-square" :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Bank & cash' => route('accounts.banking'), 'Reconcile' => null]">
        <x-button variant="secondary" :href="route('accounts.reports.show', ['report' => 'ledger', 'account' => $account->id])" wire:navigate><i class="ik ik-book-open"></i>{{ __('Register') }}</x-button>
    </x-page-header>

    @if (! $rec)
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <x-card class="xl:col-span-2" :title="__('Start a reconciliation')" :subtitle="__('Take the figures from your bank statement')">
                <form wire:submit="begin" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.input type="date" name="start.date" :label="__('Statement date (last day it covers)')" wire:model="start.date" required />
                        <x-form.input type="number" step="0.01" name="start.balance" :label="__('Closing balance on the statement')" wire:model="start.balance" required />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Bank statement CSV (optional)') }}</label>
                        <input type="file" wire:model="csv" accept=".csv,text/csv" class="block w-full text-sm text-gray-600">
                        <p class="mt-1 text-xs text-gray-400">{{ __('Download it from online banking. Columns like Date, Description and Amount (or Paid in / Paid out) are read automatically, and lines are matched to your entries.') }}</p>
                        @error('csv')<p class="mt-1 text-xs text-accent-600">{{ $message }}</p>@enderror
                    </div>
                    <x-button type="submit" wire:loading.attr="disabled"><i class="ik ik-play"></i>{{ __('Start') }}</x-button>
                </form>
            </x-card>
            <x-card no-padding :title="__('History')">
                <div class="divide-y divide-gray-50">
                    @forelse ($history as $i => $h)
                        <div class="flex items-center justify-between px-5 py-2.5 text-sm">
                            <span><span class="font-medium text-gray-700">{{ $h->statement_date->format('d M Y') }}</span>
                                <span class="block text-xs text-gray-400">{{ $cur->money($h->statement_balance) }} · {{ $h->completed_at?->format('d M Y') }}{{ $h->completer ? ' · '.$h->completer->name : '' }}</span></span>
                            @if ($i === 0)<button type="button" wire:click="reopen('{{ $h->id }}')" wire:confirm="{{ __('Re-open this reconciliation? Its entries can then be un-ticked.') }}" class="text-xs text-gray-500 hover:underline">{{ __('Re-open') }}</button>@endif
                        </div>
                    @empty
                        <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('None yet.') }}</p>
                    @endforelse
                </div>
            </x-card>
        </div>
    @else
        <div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
            <x-card>
                <p class="text-xs text-gray-400">{{ __('Statement balance at :d', ['d' => $rec->statement_date->format('d M Y')]) }}</p>
                <input type="number" step="0.01" value="{{ $rec->statement_balance }}" wire:change="updateBalance($event.target.value)"
                       class="mt-1 w-full rounded-lg border border-gray-200 px-2 py-1.5 text-lg font-semibold text-gray-800 outline-none focus:border-primary-500">
            </x-card>
            <x-stat-card :value="$cur->money($cleared)" :label="__('Cleared in the books')" icon="ik ik-check" color="primary" />
            <x-stat-card :value="$cur->money($tickedIn).' / '.$cur->money($tickedOut)" :label="__('Ticked now: in / out')" icon="ik ik-repeat" color="cyan" />
            <x-stat-card :value="$cur->money($difference)" :label="__('Difference')" :icon="'ik ik-'.(abs($difference) < 0.005 ? 'check-circle' : 'alert-circle')" :color="abs($difference) < 0.005 ? 'green' : 'accent'" />
        </div>

        <div class="grid grid-cols-1 gap-6 2xl:grid-cols-2">
            <x-card no-padding>
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-3">
                    <h3 class="font-semibold text-gray-700">{{ __('Entries in the books') }}</h3>
                    <div class="flex items-center gap-3 text-sm">
                        <label class="flex items-center gap-1.5 text-gray-500"><input type="checkbox" wire:model.live="showLater" class="rounded border-gray-300 text-primary-600">{{ __('Show later dates') }}</label>
                        <button type="button" wire:click="tickAll" class="font-medium text-primary-600 hover:underline">{{ __('Tick all') }}</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="w-10 px-3 py-2"></th><th class="px-3 py-2 font-medium">{{ __('Date') }}</th><th class="px-3 py-2 font-medium">{{ __('Details') }}</th>
                            <th class="px-3 py-2 text-right font-medium">{{ __('In') }}</th><th class="px-3 py-2 text-right font-medium">{{ __('Out') }}</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($lines as $l)
                                @php $on = $l->reconciliation_id === $rec->id; @endphp
                                <tr @class(['cursor-pointer', 'bg-green-500/10' => $on, 'hover:bg-gray-50' => ! $on]) wire:key="jl-{{ $l->id }}" wire:click="toggle('{{ $l->id }}', {{ $on ? 'false' : 'true' }})">
                                    <td class="px-3 py-2 text-center"><i class="ik {{ $on ? 'ik-check-square text-green-600' : 'ik-square text-gray-300' }}"></i></td>
                                    <td class="whitespace-nowrap px-3 py-2 text-gray-500">{{ $l->date->format('d M Y') }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $l->description ?: $l->journal->memo }}<span class="block text-xs text-gray-400">{{ $l->journal->number }}{{ $l->contact ? ' · '.$l->contact->name : '' }}</span></td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right">{{ (float) $l->debit ? $cur->money($l->debit) : '' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right">{{ (float) $l->credit ? $cur->money($l->credit) : '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">{{ __('No unreconciled entries up to this date.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card no-padding>
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-3">
                    <h3 class="font-semibold text-gray-700">{{ __('Bank statement') }}@if ($rec->source_file)<span class="ml-1 text-xs font-normal text-gray-400">{{ $rec->source_file }}</span>@endif</h3>
                    @if ($statement->isNotEmpty())<button type="button" wire:click="rematch" class="text-sm font-medium text-primary-600 hover:underline">{{ __('Match again') }}</button>@endif
                </div>
                @if ($statement->isEmpty())
                    <form wire:submit="importCsv" class="space-y-3 p-5">
                        <p class="text-sm text-gray-500">{{ __('Optional: import the statement CSV to match lines automatically. Or just tick the entries on the left against your paper / PDF statement.') }}</p>
                        <input type="file" wire:model="csv" accept=".csv,text/csv" class="block w-full text-sm text-gray-600">
                        @error('csv')<p class="text-xs text-accent-600">{{ $message }}</p>@enderror
                        <x-button type="submit" size="sm" variant="secondary">{{ __('Import') }}</x-button>
                    </form>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach ($statement as $sl)
                            <div @class(['px-4 py-2.5 text-sm', 'bg-green-500/10' => in_array($sl->status, ['matched', 'created']), 'opacity-50' => $sl->status === 'ignored']) wire:key="sl-{{ $sl->id }}">
                                <div class="flex items-start gap-3">
                                    <span class="w-20 shrink-0 text-gray-500">{{ $sl->date->format('d M') }}</span>
                                    <span class="min-w-0 flex-1 text-gray-700">{{ $sl->description }}@if ($sl->reference)<span class="block text-xs text-gray-400">{{ $sl->reference }}</span>@endif</span>
                                    <span @class(['whitespace-nowrap font-medium', 'text-green-600' => $sl->amount > 0, 'text-gray-700' => $sl->amount <= 0])>{{ $cur->money($sl->amount) }}</span>
                                </div>
                                @if ($sl->status === 'unmatched')
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <select wire:model="create.{{ $sl->id }}" class="rounded-lg border border-gray-200 bg-white py-1.5 pl-3 pr-7 text-xs text-gray-600 outline-none focus:border-primary-500" style="max-width:16rem">
                                            <option value="">{{ $sl->amount < 0 ? __('Spent on…') : __('Received for…') }}</option>
                                            @foreach ($accountGroups as $type => $group)
                                                <optgroup label="{{ __(\App\Models\Accounting\Account::TYPES[$type]) }}">
                                                    @foreach ($group as $a)<option value="{{ $a->id }}">{{ $a->code }} · {{ $a->name }}</option>@endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        <x-button size="sm" wire:click="createEntry('{{ $sl->id }}')">{{ __('Post it') }}</x-button>
                                        <button type="button" wire:click="ignore('{{ $sl->id }}')" class="text-xs text-gray-400 hover:underline">{{ __('Ignore') }}</button>
                                        @error("create.$sl->id")<span class="text-xs text-accent-600">{{ $message }}</span>@enderror
                                    </div>
                                @elseif ($sl->status === 'ignored')
                                    <button type="button" wire:click="ignore('{{ $sl->id }}', false)" class="mt-1 text-xs text-gray-400 hover:underline">{{ __('Undo ignore') }}</button>
                                @else
                                    <p class="mt-0.5 text-xs text-green-700"><i class="ik ik-check"></i> {{ $sl->status === 'created' ? __('Posted from the statement') : __('Matched') }} · {{ $sl->journalLine?->journal?->number }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <x-button variant="ghost" wire:click="discard" wire:confirm="{{ __('Delete this reconciliation? Ticks are cleared; anything posted from the statement stays posted.') }}"><i class="ik ik-trash-2"></i>{{ __('Delete') }}</x-button>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-500">{{ __('Your ticks are saved as you go.') }}</span>
                <x-button wire:click="complete" :disabled="abs($difference) >= 0.005"><i class="ik ik-check"></i>{{ __('Finish reconciliation') }}</x-button>
            </div>
        </div>
    @endif
</div>
