@props(['text' => '', 'position' => 'top'])

<span x-data="{ show: false }" @mouseenter="show = true" @mouseleave="show = false"
      @focusin="show = true" @focusout="show = false" {{ $attributes->class(['relative inline-flex']) }}>
    {{ $slot }}
    <span x-show="show" x-transition style="display:none"
          role="tooltip"
          class="pointer-events-none absolute left-1/2 z-[120] -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs font-medium text-white shadow-lg {{ $position === 'bottom' ? 'top-full mt-1.5' : 'bottom-full mb-1.5' }}">{{ $text }}</span>
</span>
