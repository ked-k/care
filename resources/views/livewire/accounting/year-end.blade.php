<div>
    <x-page-header :title="__('Close :y', ['y' => $year->name])" :subtitle="$year->starts_on->format('d M Y').' – '.$year->ends_on->format('d M Y')" icon="ik ik-lock"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'Financial years' => route('accounts.years'), 'Close' => null]">
        <x-button variant="secondary" :href="route('accounts.reports.show', ['report' => 'income-statement', 'year' => $year->id])" wire:navigate><i class="ik ik-trending-up"></i>{{ $cur->term('income_statement') }}</x-button>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-card :title="__('1. Checks')">
                <ul class="space-y-2.5 text-sm">
                    @foreach ($p['checks'] as $c)
                        <li class="flex items-start gap-3">
                            <i @class(['ik mt-0.5', 'ik-check-circle text-green-600' => $c['ok'], 'ik-x-circle text-accent-500' => ! $c['ok'] && $c['block'], 'ik-alert-triangle text-amber-500' => ! $c['ok'] && ! $c['block']])></i>
                            <span><span class="text-gray-700">{{ $c['label'] }}</span>
                                @unless ($c['ok'])<span class="block text-xs {{ $c['block'] ? 'text-accent-600' : 'text-amber-600' }}">{{ $c['block'] ? __('Required:') : __('Recommended:') }} {{ $c['fix'] }}</span>@endunless</span>
                        </li>
                    @endforeach
                </ul>
            </x-card>

            <x-card :title="__('2. The year’s result')">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">{{ $cur->term('income') }}</dt><dd>{{ $cur->money($p['is']['revenue']['current'] + $p['is']['other']['current']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ $cur->term('expenses') }} {{ __('and cost of sales') }}</dt><dd>{{ $cur->money($p['is']['cos']['current'] + $p['is']['totalExp']['current'] + $p['is']['tax']['current']) }}</dd></div>
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-semibold"><dt>{{ $p['result'] >= 0 ? $cur->term('result') : $cur->term('loss') }}</dt><dd>{{ $cur->money($p['result']) }}</dd></div>
                </dl>
                <p class="mt-3 text-xs text-gray-400">{{ __(':n income and expense accounts are cleared to zero and the result moves to :r. This year’s statements still show the full figures.', ['n' => count($p['closing']), 'r' => mb_strtolower($cur->term('retained'))]) }}</p>
            </x-card>

            <x-card :title="__('3. Appropriations')" :subtitle="__('Set in Finance → Settings')">
                @if (empty($p['appropriations']))
                    <p class="text-sm text-gray-500">{{ __('None set up — the whole result stays in :r.', ['r' => mb_strtolower($cur->term('retained'))]) }}
                        <a href="{{ route('accounts.settings') }}" wire:navigate class="text-primary-600 hover:underline">{{ __('Set them up') }}</a></p>
                @else
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($p['appropriations'] as $a)
                                <tr><td class="py-2 text-gray-700">{{ $a['name'] }}<span class="block text-xs text-gray-400">{{ match ($a['basis']) { 'surplus' => __(':r% of the :t', ['r' => $a['rate'], 't' => mb_strtolower($cur->term('result_short'))]), 'turnover' => __(':r% of turnover (:t)', ['r' => $a['rate'], 't' => $cur->money($p['turnover'])]), default => __('Fixed amount') } }} → {{ $a['account']?->label() ?? __('no account chosen') }}</span></td>
                                    <td class="py-2 text-right font-medium">{{ $cur->money($a['amount']) }}</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200"><td class="py-2 font-semibold">{{ __('Total set aside') }}</td><td class="py-2 text-right font-semibold">{{ $cur->money($p['appropriated']) }}</td></tr>
                            <tr><td class="py-1 text-gray-500">{{ __('Left in :r', ['r' => mb_strtolower($cur->term('retained'))]) }}</td><td class="py-1 text-right text-gray-700">{{ $cur->money($p['retained']) }}</td></tr>
                        </tfoot>
                    </table>
                    @if ($p['result'] <= 0)<p class="mt-2 text-xs text-amber-600">{{ __('There’s no surplus this year, so nothing is appropriated.') }}</p>@endif
                @endif
            </x-card>
        </div>

        <div>
            <x-card :title="__('4. Close')">
                @if ($year->isClosed())
                    <p class="text-sm text-gray-600">{{ __(':y is already closed.', ['y' => $year->name]) }}</p>
                @else
                    <p class="text-sm text-gray-600">{{ __('This posts the closing and appropriation journals on :d, marks the year closed and locks the books up to that date. You can re-open it later with a reason.', ['d' => $year->ends_on->format('d M Y')]) }}</p>
                    <label class="mt-4 flex items-start gap-2 text-sm text-gray-700"><input type="checkbox" wire:model.live="confirmed" class="mt-0.5 rounded border-gray-300 text-primary-600">
                        <span>{{ __('The figures for :y are final.', ['y' => $year->name]) }}</span></label>
                    @error('confirmed')<p class="mt-1 text-xs text-accent-600">{{ $message }}</p>@enderror
                    <x-button class="mt-4 w-full justify-center" wire:click="close" :disabled="! $p['canClose'] || ! $confirmed"><i class="ik ik-lock"></i>{{ __('Close :y', ['y' => $year->name]) }}</x-button>
                    @unless ($p['canClose'])<p class="mt-2 text-xs text-accent-600">{{ __('Fix the required items first.') }}</p>@endunless
                @endif
            </x-card>
        </div>
    </div>
</div>
