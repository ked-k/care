@props(['items' => []])

@php
    $user = auth()->user();

    // An entry is visible when it has no 'can' rule, or the user passes it.
    $allowed = function ($item) use ($user) {
        return empty($item['can']) || ($user && $user->can($item['can']));
    };

    // Resolve href from a 'route' (named) or 'url' (path) key.
    $href = function ($item) {
        if (!empty($item['route'])) {
            return route($item['route']);
        }
        return url($item['url'] ?? '#');
    };

    // Active when one of the entry's request()->is() patterns matches,
// or (for groups) any of its children are active.
// $isActive = function ($item) use (&$isActive) {
//     if (! empty($item['active'])) {
//         $patterns = is_array($item['active']) ? $item['active'] : explode('|', $item['active']);
//         if (request()->is(...$patterns)) return true;
//     }
//     foreach ($item['children'] ?? [] as $child) {
//         if ($isActive($child)) return true;
//     }
//     return false;
// };
$isActive = function ($item) use (&$isActive) {
    if (!empty($item['route']) && request()->routeIs($item['route'])) {
        return true;
    }

    if (!empty($item['active'])) {
        $patterns = is_array($item['active']) ? $item['active'] : explode('|', $item['active']);

        if (request()->is(...$patterns)) {
            return true;
        }
    }

    foreach ($item['children'] ?? [] as $child) {
        if ($isActive($child)) {
            return true;
        }
    }

    return false;
};

$badge = function ($item) {
    if (empty($item['badge'])) {
        return '';
    }
    $color = ($item['badge']['color'] ?? 'green') === 'danger' ? 'bg-accent-500' : 'bg-green-500';
    return '<span class="rounded ' .
        $color .
        ' px-1.5 py-0.5 text-[10px] font-semibold text-white">' .
        e(__($item['badge']['text'])) .
        '</span>';
    };
@endphp

@foreach ($items as $item)
    @if (!empty($item['heading']))
        <x-nav.label>{{ __($item['heading']) }}</x-nav.label>
    @elseif (!empty($item['children']) && count(array_filter($item['children'], $allowed)))
        <x-nav.group icon="{{ $item['icon'] ?? '' }}" label="{{ __($item['label']) }}" :active="$isActive($item)">
            <x-slot:trailing>{!! $badge($item) !!}</x-slot:trailing>
            @foreach (array_filter($item['children'], $allowed) as $child)
                <x-nav.sublink href="{{ $href($child) }}" icon="{{ $child['icon'] ?? '' }}"
                    :active="$isActive($child)">{{ __($child['label']) }}</x-nav.sublink>
            @endforeach
        </x-nav.group>
    @elseif (empty($item['children']) && $allowed($item))
        <x-nav.item href="{{ $href($item) }}" icon="{{ $item['icon'] ?? '' }}" :active="$isActive($item)">
            {{ __($item['label']) }}
            <x-slot:trailing>{!! $badge($item) !!}</x-slot:trailing>
        </x-nav.item>
    @endif
@endforeach
