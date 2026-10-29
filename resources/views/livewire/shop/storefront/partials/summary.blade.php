{{-- Batch 40: totals block shared by the basket and checkout. Needs $totals, $store; optional $showDiscountForm. --}}
<div class="s-sum">
    <div class="s-sum-row"><span>{{ __('Items (:n)', ['n' => $totals['count']]) }}</span><span class="num">{{ $store->money($totals['subtotal']) }}</span></div>

    @if ($totals['discount'] > 0 || $totals['discount_code'])
        <div class="s-sum-row is-discount">
            <span>
                {{ __('Discount') }} <span class="s-tag s-tag-ok">{{ $totals['discount_code'] }}</span>
                <button type="button" class="s-btn-link s-btn s-small" wire:click="removeDiscount" aria-label="{{ __('Remove discount') }}">{{ __('Remove') }}</button>
            </span>
            <span class="num">{{ $totals['discount'] > 0 ? '−'.$store->money($totals['discount']) : $totals['discount_label'] }}</span>
        </div>
    @endif

    <div class="s-sum-row">
        <span>{{ $totals['delivery_method']?->name ?? __('Delivery') }}</span>
        <span class="num">{{ $totals['delivery'] > 0 ? $store->money($totals['delivery']) : __('Free') }}</span>
    </div>

    @if (! $totals['prices_include_vat'])
        <div class="s-sum-row"><span>{{ __('VAT') }}</span><span class="num">{{ $store->money($totals['vat']) }}</span></div>
    @endif

    @if ($totals['free_delivery_gap'] !== null && $totals['free_delivery_gap'] > 0)
        @php $goal = (float) $totals['delivery_method']->free_over; @endphp
        <div class="s-free-gap">
            {{ __('Add :amount more for free delivery.', ['amount' => $store->money($totals['free_delivery_gap'])]) }}
            <div class="s-bar"><span style="width: {{ $goal > 0 ? min(100, round($totals['subtotal'] / $goal * 100)) : 0 }}%"></span></div>
        </div>
    @endif

    <div class="s-sum-total">
        <span class="s-strong">{{ __('Total') }}</span>
        <strong class="num">{{ $store->money($totals['total']) }}</strong>
    </div>
    @if ($totals['prices_include_vat'] && $totals['vat'] > 0)
        <div class="s-sum-vat num">{{ __('Includes :amount VAT', ['amount' => $store->money($totals['vat'])]) }}</div>
    @endif

    @if ($showDiscountForm ?? false)
        @if ($totals['discount_error'])
            <p class="s-error">{{ $totals['discount_error'] }}</p>
        @endif
        @unless ($totals['discount_code'])
            {{-- Not a <form>: on checkout this sits inside the main order form, and forms can't nest. --}}
            <div class="s-code">
                <label for="discount-code" class="sr-only">{{ __('Discount code') }}</label>
                <input id="discount-code" type="text" class="s-input @error('discountCode') is-invalid @enderror" wire:model="discountCode"
                       wire:keydown.enter.prevent="applyDiscount" placeholder="{{ __('Discount code') }}" autocomplete="off">
                <button type="button" class="s-btn s-btn-ghost" wire:click="applyDiscount">{{ __('Apply') }}</button>
            </div>
            @error('discountCode')<p class="s-error">{{ $message }}</p>@enderror
        @endunless
    @endif
</div>
