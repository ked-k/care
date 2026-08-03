@props([
    'name',
    'label' => null,
    'value' => null,
    'required' => false,
    'rows' => 3,
    'hint' => null,
])

@php $hasError = $errors->has($name); @endphp

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-gray-700">
            {{ $label }}@if ($required)<span class="text-accent-500">*</span>@endif
        </label>
    @endif
    <textarea name="{{ $name }}" id="{{ $name }}" rows="{{ $rows }}"
        @if ($required) required @endif
        {{ $attributes->class([
            'w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-gray-700 shadow-sm transition outline-none placeholder:text-gray-400',
            'border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-100' => ! $hasError,
            'border-accent-400 focus:ring-2 focus:ring-accent-400/25' => $hasError,
        ]) }}>{{ old($name, $value) }}</textarea>
    @if ($hasError)
        <p class="mt-1 text-xs text-accent-600">{{ $errors->first($name) }}</p>
    @elseif ($hint)
        <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
    @endif
</div>
