@props(['align' => 'right', 'width' => 'w-56'])

@php
    $alignment = $align === 'left' ? 'left-0 origin-top-left' : 'right-0 origin-top-right';
@endphp

<div x-data="{ open: false }" class="relative" @click.outside="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open" style="display:none"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute z-50 mt-2 {{ $alignment }} {{ $width }} rounded-lg border border-gray-100 bg-white py-1 shadow-lg shadow-black/5 ring-1 ring-black/5">
        {{ $slot }}
    </div>
</div>
