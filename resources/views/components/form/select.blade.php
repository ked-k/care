@props([
    'name',
    'label' => null,
    'required' => false,
    'hint' => null,
    'multiple' => false,
])

@php $hasError = $errors->has($name); @endphp

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-gray-700">
            {{ $label }}@if ($required)<span class="text-accent-500">*</span>@endif
        </label>
    @endif
    <div class="relative">
        <select name="{{ $multiple ? $name.'[]' : $name }}" id="{{ $name }}"
            @if ($required) required @endif @if ($multiple) multiple @endif
            {{ $attributes->class([
                'w-full appearance-none rounded-lg border bg-white py-2.5 pl-3 pr-9 text-sm text-gray-700 shadow-sm transition outline-none',
                'border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100' => ! $hasError,
                'border-accent-400 focus:ring-2 focus:ring-accent-400/25' => $hasError,
            ]) }}>
            {{ $slot }}
        </select>
        @unless ($multiple)
            <i class="ik ik-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        @endunless
    </div>
    @if ($hasError)
        <p class="mt-1 text-xs text-accent-600">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
    @endif
</div>
