@props([
    'name',
    'label' => null,
    'checked' => false,
    'value' => 1,
    'color' => 'primary',
])

@php
    $on = [
        'primary' => 'bg-primary-500', 'green' => 'bg-green-500', 'accent' => 'bg-accent-500', 'amber' => 'bg-amber-500',
    ][$color] ?? 'bg-primary-500';
    $isChecked = old($name, $checked) ? true : false;
@endphp

<label class="flex cursor-pointer items-center gap-3" x-data="{ on: {{ $isChecked ? 'true' : 'false' }} }">
    <button type="button" role="switch" :aria-checked="on" @click="on = !on"
            :class="on ? '{{ $on }}' : 'bg-gray-200'"
            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-200">
        <span :class="on ? 'translate-x-5' : 'translate-x-1'"
              class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200"></span>
    </button>
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" class="hidden" :checked="on" x-model="on">
    @if ($label)<span class="text-sm text-gray-600">{{ $label }}</span>@endif
</label>
