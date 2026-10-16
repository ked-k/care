@props([
    'wordmark' => true, // show the "CareAffinity" wordmark next to the mark
    'markClass' => 'h-9 w-9', // size of the logo mark
    'textClass' => 'text-lg', // size of the wordmark
])

{{--
    Batch 13: swapped the hand-drawn placeholder SVG for the real brand
    logo mark (heart containing two people, a medical cross and a house),
    cropped to just the icon and matted to transparency so it drops onto any
    background (sidebar dark, topbar light, carer bottom-bar, etc.).

    Batch 26: renamed the platform from CareTrust to CareAffinity — same
    mark and navy colour, only the wordmark text changed. The underlying
    image file is still called caretrust-mark.png; nothing on screen
    references that filename, so it didn't need renaming too.
--}}
<span {{ $attributes->class('inline-flex items-center gap-2.5') }}>
    <img src="{{ asset('images/brand/caretrust-mark.png') }}" class="{{ $markClass }} shrink-0 object-contain"
        alt="{{ config('app.name', 'Care') }}">

    @if ($wordmark)
        <span class="{{ $textClass }} font-bold leading-none tracking-tight">Care<span
                class="text-primary-500">Affinity</span></span>
    @endif
</span>
