@props(['name', 'title' => null, 'maxWidth' => 'max-w-lg'])

{{-- Open with  $dispatch('open-modal', '{{ $name }}')  ·  close with  'close-modal' --}}
<div x-data="{ open: false }"
     x-on:open-modal.window="$event.detail === '{{ $name }}' && (open = true)"
     x-on:close-modal.window="$event.detail === '{{ $name }}' && (open = false)"
     x-show="open" x-transition.opacity style="display:none"
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto bg-black/40 p-4 sm:items-center">
    <div x-show="open" x-trap.noscroll="open" @click.outside="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-3 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         role="dialog" aria-modal="true"
         class="my-8 w-full {{ $maxWidth }} rounded-2xl bg-white shadow-xl">
        @if ($title || isset($header))
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h3 class="font-semibold text-gray-800">{{ $header ?? $title }}</h3>
                <button @click="open = false" class="text-gray-400 transition hover:text-gray-600" aria-label="{{ __('Close') }}"><i class="ik ik-x"></i></button>
            </div>
        @endif
        <div class="p-5">{{ $slot }}</div>
        @isset($footer)
            <div class="flex flex-wrap justify-end gap-2 border-t border-gray-100 px-5 py-4">{{ $footer }}</div>
        @endisset
    </div>
</div>
