@props([
    'name',
    'label' => null,
    'value' => 1,
    'checked' => false,
    'type' => 'checkbox',   // checkbox | radio
])

<label class="flex cursor-pointer items-center gap-2.5 text-sm text-gray-600">
    <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" @checked(old($name, $checked))
        {{ $attributes->class([
            'h-4 w-4 border-gray-300 text-primary-600 shadow-sm focus:ring-2 focus:ring-primary-200',
            'rounded' => $type === 'checkbox',
            'rounded-full' => $type === 'radio',
        ]) }}>
    @if ($label){{ $label }}@endif
</label>
