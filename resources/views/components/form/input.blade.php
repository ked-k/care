@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'icon' => null,
    'hint' => null,
])

@php $hasError = $errors->has($name); @endphp

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-gray-700">
            {{ $label }}@if ($required)<span class="text-accent-500">*</span>@endif
        </label>
    @endif
    <div class="relative">
        @if ($icon)
            <i class="{{ $icon }} pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        @endif
        <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $value) }}"
            @if ($required) required @endif
            {{ $attributes->class([
                'w-full rounded-lg border bg-white py-2.5 text-sm text-gray-700 shadow-sm transition outline-none placeholder:text-gray-400',
                'pl-10 pr-3' => $icon,
                'px-3' => ! $icon,
                'border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100' => ! $hasError,
                'border-accent-400 focus:border-accent-500 focus:ring-2 focus:ring-accent-400/25' => $hasError,
            ]) }}>
    </div>
    @if ($hasError)
        <p class="mt-1 text-xs text-accent-600">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
    @endif
</div>
