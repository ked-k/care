@props(['name', 'title' => null, 'width' => 'w-96'])

{{-- Open with  $dispatch('open-drawer', '{{ $name }}') --}}
<div x-data="{ open: false }"
     x-on:open-drawer.window="$event.detail === '{{ $name }}' && (open = true)"
     x-on:close-drawer.window="$event.detail === '{{ $name }}' && (open = false)"
     x-show="open" x-transition.opacity style="display:none"
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-[90] bg-black/40">
    <aside x-show="open" x-trap.noscroll="open" @click.outside="open = false"
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
           role="dialog" aria-modal="true"
           class="absolute inset-y-0 right-0 flex {{ $width }} max-w-full flex-col bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h3 class="font-semibold text-gray-800">{{ $title }}</h3>
            <button @click="open = false" class="text-gray-400 transition hover:text-gray-600" aria-label="{{ __('Close') }}"><i class="ik ik-x"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto p-5">{{ $slot }}</div>
        @isset($footer)
            <div class="flex flex-wrap justify-end gap-2 border-t border-gray-100 px-5 py-4">{{ $footer }}</div>
        @endisset
    </aside>
</div>
