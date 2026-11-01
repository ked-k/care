<div>
    <x-page-header :title="($j->number)" :subtitle="($j->typeLabel()).' · '.($j->date->format('d M Y')).' · '.($j->financialYear?->name)" icon="ik ik-book"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Journals' => route('accounts.journals'), $j->number => null]">
        @if ($url = $j->sourceUrl())
            <x-button variant="secondary" :href="($url)" wire:navigate><i class="ik ik-link"></i>{{ __('Open the source record') }}</x-button>
        @endif
        @if (in_array($j->type, ['manual', 'opening', 'transfer']) && ! $j->isReversed())
            <x-button variant="ghost" @click="$dispatch('open-modal', 'reverse')"><i class="ik ik-rotate-ccw"></i>{{ __('Reverse') }}</x-button>
        @endif
    </x-page-header>

    @if ($j->reverses)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">{{ __('This reverses') }} <a href="{{ route('accounts.journals.show', $j->reverses_id) }}" wire:navigate class="font-medium underline">{{ $j->reverses->number }}</a>.</div>
    @elseif ($j->reversedBy)
        <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">{{ __('Reversed by') }} <a href="{{ route('accounts.journals.show', $j->reversed_by_id) }}" wire:navigate class="font-medium underline">{{ $j->reversedBy->number }}</a> {{ __('on :d', ['d' => $j->reversedBy->date->format('d M Y')]) }}.</div>
    @endif

    <x-card no-padding>
        <div class="border-b border-gray-100 p-5">
            <p class="font-medium text-gray-800">{{ $j->memo }}</p>
            <p class="mt-1 text-sm text-gray-400">{{ $j->reference }}{{ $j->reference ? ' · ' : '' }}{{ __('Posted :d', ['d' => $j->created_at->format('d M Y H:i')]) }}{{ $j->creator ? ' · '.$j->creator->name : ' · '.__('automatically') }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Account') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Description') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Debit') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Credit') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($j->lines as $l)
                        <tr>
                            <td class="px-5 py-3"><a href="{{ route('accounts.reports.show', ['report' => 'ledger', 'account' => $l->account_id]) }}" wire:navigate class="text-gray-700 hover:text-primary-600">{{ $l->account->label() }}</a>
                                @if ($l->contact)<span class="block text-xs text-gray-400"><a href="{{ route('accounts.contacts.show', $l->contact_id) }}" wire:navigate class="hover:underline">{{ $l->contact->name }}</a></span>@endif</td>
                            <td class="px-5 py-3 text-gray-500">{{ $l->description }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">{{ (float) $l->debit ? $cur->money($l->debit) : '' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">{{ (float) $l->credit ? $cur->money($l->credit) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200 font-semibold text-gray-800"><td colspan="2" class="px-5 py-3">{{ __('Total') }}</td>
                        <td class="px-5 py-3 text-right">{{ $cur->money($j->lines->sum('debit')) }}</td><td class="px-5 py-3 text-right">{{ $cur->money($j->lines->sum('credit')) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </x-card>

    <x-modal name="reverse" :title="(__('Reverse :n', ['n' => $j->number]))">
        <form wire:submit="reverse" class="space-y-4">
            <p class="text-sm text-gray-600">{{ __('A new journal with every debit and credit swapped is posted, dated the same day (or the first open day if that period is locked).') }}</p>
            <x-form.textarea name="reason" :label="(__('Reason'))" wire:model="reason" rows="2" required />
            <div class="flex justify-end gap-2">
                <x-button variant="ghost" @click="$dispatch('close-modal', 'reverse')">{{ __('Cancel') }}</x-button>
                <x-button type="submit" variant="danger">{{ __('Reverse it') }}</x-button>
            </div>
        </form>
    </x-modal>
</div>
