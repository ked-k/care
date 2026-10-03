@props([
    'wordmark' => true, // show the "CareTrust" wordmark next to the mark
    'markClass' => 'h-9 w-9', // size of the logo mark
    'textClass' => 'text-lg', // size of the wordmark
])

{{--
    Batch 13: swapped the hand-drawn placeholder SVG for the real CareTrust
    logo mark (heart containing two people, a medical cross and a house),
    cropped to just the icon and matted to transparency so it drops onto any
    background (sidebar dark, topbar light, carer bottom-bar, etc.).
--}}
<span {{ $attributes->class('inline-flex items-center gap-2.5') }}>
    <img src="{{ asset('images/brand/caretrust-mark.png') }}" class="{{ $markClass }} shrink-0 object-contain"
        alt="{{ config('app.name', 'Care') }}">

    @if ($wordmark)
        <span class="{{ $textClass }} font-bold leading-none tracking-tight">Care<span
                class="text-primary-500">Trust</span></span>
    @endif
</span>
