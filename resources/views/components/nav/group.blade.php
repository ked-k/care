@props(['icon' => null, 'label' => '', 'active' => false])

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }">
    <button type="button" @click="open = ! open" @class([
            'group flex w-full items-center gap-3 border-l-2 px-5 py-2.5 text-sm transition-colors',
            'border-accent-500 bg-[#80808033] font-medium text-sidebar-text' => $active,
            'border-transparent text-sidebar-text/80 hover:bg-[#80808022] hover:text-sidebar-text' => ! $active,
        ])>
        @if ($icon)
            <i @class([$icon, 'w-5 text-center text-base', 'text-sidebar-text' => $active, 'text-sidebar-icon group-hover:text-sidebar-text' => ! $active])></i>
        @endif
        <span class="flex-1 text-left">{{ $label }}</span>
        {{ $trailing ?? '' }}
        <i class="ik ik-chevron-right text-xs transition-transform" :class="open && 'rotate-90'"></i>
    </button>
    <div x-show="open" x-collapse class="bg-black/20" style="display:none">
        {{ $slot }}
    </div>
</div>
