@props(['href' => '#', 'active' => false, 'icon' => null])

<a href="{{ $href }}" @class([
        'group flex items-center gap-2.5 py-2 pl-12 pr-5 text-sm transition-colors',
        'font-medium text-sidebar-text' => $active,
        'text-sidebar-text/65 hover:bg-[#80808015] hover:text-sidebar-text' => ! $active,
    ])>
    @if ($icon)
        <i @class([$icon, 'w-4 text-center text-[13px]', 'text-sidebar-text' => $active, 'text-sidebar-icon group-hover:text-sidebar-text' => ! $active])></i>
    @endif
    <span class="flex-1">{{ $slot }}</span>
</a>
