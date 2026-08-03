@props([
    'wordmark' => true,          // show the "Radminly" wordmark next to the mark
    'markClass' => 'h-9 w-9',    // size of the logo mark
    'textClass' => 'text-lg',    // size of the wordmark
])

@php $gid = 'radminly-grad-'.uniqid(); @endphp

<span {{ $attributes->class('inline-flex items-center gap-2.5') }}>
    {{-- Logomark: gradient squircle with an abstract admin-dashboard glyph (sidebar + content cards) --}}
    <svg class="{{ $markClass }} shrink-0" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg"
         role="img" aria-label="{{ config('app.name', 'Radminly') }}">
        <defs>
            <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="40" y2="40" gradientUnits="userSpaceOnUse">
                <stop stop-color="#2563eb"/>
                <stop offset="1" stop-color="#7c3aed"/>
            </linearGradient>
        </defs>
        <rect width="40" height="40" rx="11" fill="url(#{{ $gid }})"/>
        <rect x="9" y="11" width="5.5" height="18" rx="2.75" fill="#fff" fill-opacity="0.95"/>
        <rect x="18.5" y="11" width="12.5" height="7.5" rx="3" fill="#fff" fill-opacity="0.92"/>
        <rect x="18.5" y="21.5" width="12.5" height="7.5" rx="3" fill="#fff" fill-opacity="0.55"/>
    </svg>

    @if ($wordmark)
        <span class="{{ $textClass }} font-bold leading-none tracking-tight">Radmin<span class="text-primary-500">ly</span></span>
    @endif
</span>
