@props(['href' => '#', 'icon' => null, 'active' => false])

<a href="{{ $href }}" @class([
        'group flex items-center gap-3 border-l-2 px-5 py-2.5 text-sm transition-colors',
        'border-accent-500 bg-[#80808033] font-medium text-sidebar-text' => $active,
        'border-transparent text-sidebar-text/80 hover:bg-[#80808022] hover:text-sidebar-text' => ! $active,
    ])>
    @if ($icon)
        <i @class([$icon, 'w-5 text-center text-base', 'text-sidebar-text' => $active, 'text-sidebar-icon group-hover:text-sidebar-text' => ! $active])></i>
    @endif
    <span class="flex-1">{{ $slot }}</span>
    {{ $trailing ?? '' }}
</a>
