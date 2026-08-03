@props(['icon' => 'ik ik-inbox', 'title' => 'Nothing here yet', 'description' => null, 'compact' => false])

<div class="flex flex-col items-center justify-center px-6 text-center {{ $compact ? 'py-10' : 'py-16' }}">
    <span class="mb-4 flex {{ $compact ? 'h-12 w-12' : 'h-16 w-16' }} items-center justify-center rounded-2xl bg-gray-100 text-gray-300">
        <i class="{{ $icon }} {{ $compact ? 'text-2xl' : 'text-3xl' }}"></i>
    </span>
    <h3 class="text-base font-semibold text-gray-700">{{ $title }}</h3>
    @if ($description)<p class="mt-1 max-w-sm text-sm text-gray-400">{{ $description }}</p>@endif
    @isset($action)<div class="mt-5">{{ $action }}</div>@endisset
</div>
