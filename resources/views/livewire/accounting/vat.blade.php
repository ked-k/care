<div>
    @php $months = (int) ($cur->vat_period_months ?: 3); @endphp
    <x-page-header :title="__('VAT')" :subtitle="__('Worked out from the books, on invoice and bill dates')" icon="ik ik-percent"
                   :breadcrumbs="['Finance' => route('accounts.dashboard'), 'VAT' => null]">
        <x-button variant="secondary" @click="$dispatch('open-modal', 'vat-setup')" class="whitespace-nowrap"><i class="ik ik-settings"></i>{{ __('VAT periods') }}</x-button>
        <x-button variant="secondary" :href="route('accounts.vat.print', ['period' => $start->toDateString()])" target="_blank"><i class="ik ik-printer"></i>{{ __('Print') }}</x-button>
        @if ($filed)
            <x-button :href="route('accounts.vat.show', $filed->id)" class="whitespace-nowrap" wire:navigate><i class="ik ik-check-circle"></i>{{ __('Open filed return') }}</x-button>
        @elseif ($isNext)
            <x-button @click="$dispatch('open-modal', 'vat-file')" class="whitespace-nowrap" :disabled="! $canFile"><i class="ik ik-send"></i>{{ __('Mark as filed') }}</x-button>
        @endif
    </x-page-header>

    @unless ($cur->vat_registered)
        <x-alert type="warning" class="mb-4">{{ __('The organisation isn’t marked as VAT registered (Finance → Settings). New invoices and bills won’t add VAT until it is.') }}</x-alert>
    @endunless

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <x-button variant="secondary" size="sm" wire:click="move(-1)"><i class="ik ik-chevron-left"></i></x-button>
        <div>
            <span class="text-lg font-semibold text-gray-800">{{ $start->format('j M Y') }} – {{ $end->format('j M Y') }}</span>
            <span class="ml-2 text-sm text-gray-500">
                @if ($filed){{ __('Filed :d', ['d' => $filed->filed_at?->format('d M Y')]) }}
                @elseif ($isNext){{ $end->lt(today()) ? __('Due by :d', ['d' => \Illuminate\Support\Carbon::parse($calc['due'])->format('j M Y')]) : __('Period still running — can be filed from :d', ['d' => $end->copy()->addDay()->format('j M Y')]) }}
                @else{{ __('Not the next return to file — shown for checking') }}@endif
            </span>
        </div>
        <x-button variant="secondary" size="sm" wire:click="move(1)"><i class="ik ik-chevron-right"></i></x-button>
        @if ($period)<button type="button" wire:click="$set('period', '')" class="text-sm text-primary-600 hover:underline">{{ __('Back to the next return') }}</button>@endif
        <span wire:loading class="text-xs text-gray-400">{{ __('Working…') }}</span>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-card :title="__('The return')">
                @include('accounting.vat._boxes')
                @if ($calc['late']->isNotEmpty())
                    <p class="mt-3 text-sm text-amber-600"><i class="ik ik-alert-circle mr-1"></i>{{ trans_choice(':n entry is dated in a period already filed and is included here instead.|:n entries are dated in periods already filed and are included here instead.', $calc['late']->count(), ['n' => $calc['late']->count()]) }}</p>
                @endif
                @if (! $filed && abs($calc['vatBalance'] - $calc['boxes']['box5']) >= 0.01)
                    <p class="mt-3 text-xs text-gray-400">{{ __('The VAT accounts show :a owed at :d. That differs from box 5 when earlier returns are still unpaid, or VAT was entered with a manual journal.', ['a' => $cur->money($calc['vatBalance']), 'd' => $end->format('j M Y')]) }}</p>
                @endif
            </x-card>

            <x-card no-padding>
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
                    <h3 class="font-semibold text-gray-800">{{ __('What’s in it') }}</h3>
                    <div class="flex gap-1 text-sm">
                        @foreach (['' => __('All'), 'sales' => __('Sales'), 'purchases' => __('Purchases'), 'adjustments' => __('Adjustments')] as $k => $label)
                            <button type="button" wire:click="$set('side', '{{ $k }}')" @class(['rounded-lg px-3 py-1.5', 'bg-primary-500/10 font-medium text-primary-600' => $side === $k, 'text-gray-500 hover:bg-gray-50' => $side !== $k])>
                                {{ $label }}@if ($k && isset($calc['bySide'][$k])) <span class="text-xs text-gray-400">{{ $calc['bySide'][$k]['count'] }}</span>@endif
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="max-h-[32rem] overflow-y-auto px-2 pb-2">
                    @include('accounting.vat._rows', ['rows' => $rows, 'links' => true])
                </div>
            </x-card>
        </div>

        <div class="space-y-6 xl:col-span-1">
            <x-card no-padding :title="__('Returns filed')">
                <div class="divide-y divide-gray-50">
                    @forelse ($returns as $r)
                        <a href="{{ route('accounts.vat.show', $r->id) }}" wire:navigate class="flex items-center justify-between px-5 py-3 text-sm hover:bg-gray-50" wire:key="vr-{{ $r->id }}">
                            <span><span class="font-medium text-gray-700">{{ $r->period_start->format('j M Y') }} – {{ $r->period_end->format('j M Y') }}</span>
                                <span class="block text-xs text-gray-400">{{ __('Filed :d', ['d' => $r->filed_at?->format('d M Y')]) }}{{ $r->reference ? ' · '.$r->reference : '' }}</span></span>
                            <span class="text-right"><span class="font-semibold text-gray-700">{{ $cur->money(abs($r->net())) }}</span>
                                <span class="block">@if ($r->isPaid())<x-badge color="success">{{ $r->net() < 0 ? __('Refunded') : __('Paid') }}</x-badge>@else<x-badge color="warning">{{ $r->net() < 0 ? __('Refund due') : __('To pay') }}</x-badge>@endif</span></span>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('None yet.') }}</p>
                    @endforelse
                </div>
            </x-card>
            <x-card :title="__('How the figures are worked out')">
                <ul class="list-disc space-y-1.5 pl-4 text-sm text-gray-500">
                    <li>{{ __('Sales invoices, credit notes, money in and shop sales: their VAT goes in box 1 and their value (without VAT) in box 6.') }}</li>
                    <li>{{ __('Supplier bills, supplier credit notes, money out and goods received: VAT in box 4, value in box 7.') }}</li>
                    <li>{{ __('Any other journal that touches a VAT account is an adjustment — added to box 1 or box 4.') }}</li>
                    <li>{{ __('Voided documents drop out by themselves. Boxes 6 to 9 are whole pounds.') }}</li>
                    <li>{{ __('Record paying the VAT from the filed return, so it isn’t counted as a purchase.') }}</li>
                </ul>
            </x-card>
        </div>
    </div>

    <x-modal name="vat-file" :title="__('Mark :a – :b as filed', ['a' => $start->format('j M Y'), 'b' => $end->format('j M Y')])" max-width="max-w-lg">
        <form wire:submit="fileReturn" class="space-y-4">
            <p class="text-sm text-gray-600">{{ __('Submit the figures to the tax authority (HMRC’s Making Tax Digital software or online account), then record it here. The figures are frozen and every entry used is marked, so nothing is counted twice.') }}</p>
            <div class="rounded-lg bg-gray-50 px-4 py-3 text-sm">
                <span class="text-gray-500">{{ __('Box 5') }}:</span> <strong>{{ $cur->money(abs($calc['boxes']['box5'])) }}</strong> {{ $calc['boxes']['box5'] < 0 ? __('to reclaim') : __('to pay') }}
            </div>
            <x-form.input name="file.reference" :label="__('Submission reference (optional)')" wire:model="file.reference" />
            <x-form.textarea name="file.notes" :label="__('Notes')" wire:model="file.notes" rows="2" />
            <x-form.checkbox name="file.lock" :label="__('Lock the books up to :d so nothing else can be dated into this period', ['d' => $end->format('j M Y')])" wire:model="file.lock" />
            <div class="flex justify-end gap-2">
                <x-button variant="ghost" @click="$dispatch('close-modal', 'vat-file')">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Mark as filed') }}</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="vat-setup" :title="__('VAT periods')" max-width="max-w-md">
        <form wire:submit="saveSetup" class="space-y-4">
            <x-form.select name="setup.vat_period_months" :label="__('How often you file')" wire:model="setup.vat_period_months">
                <option value="3">{{ __('Quarterly') }}</option>
                <option value="1">{{ __('Monthly') }}</option>
                <option value="12">{{ __('Yearly') }}</option>
            </x-form.select>
            <x-form.select name="setup.vat_period_end_month" :label="__('A period ends in')" wire:model="setup.vat_period_end_month">
                @foreach (range(1, 12) as $m)<option value="{{ $m }}">{{ \Illuminate\Support\Carbon::create(2000, $m, 1)->format('F') }}</option>@endforeach
            </x-form.select>
            <p class="-mt-2 text-xs text-gray-400">{{ __('For quarters ending March, June, September and December choose March (or any of them).') }}</p>
            <x-form.input type="date" name="setup.vat_start_date" :label="__('First day of your first return here (optional)')" wire:model="setup.vat_start_date" />
            <div class="flex justify-end gap-2">
                <x-button variant="ghost" @click="$dispatch('close-modal', 'vat-setup')">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Save') }}</x-button>
            </div>
        </form>
    </x-modal>
</div>
