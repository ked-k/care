@props(['tabs' => [], 'default' => null])

{{-- Usage:
     <x-tabs :tabs="['profile' => 'Profile', 'security' => 'Security']">
        <div x-show="tab === 'profile'"> ... </div>
        <div x-show="tab === 'security'" x-cloak> ... </div>
     </x-tabs>
--}}
<div x-data="{ tab: @js($default ?? array_key_first($tabs)) }" {{ $attributes }}>
    <div class="flex gap-1 overflow-x-auto border-b border-gray-200" role="tablist">
        @foreach ($tabs as $key => $label)
            <button type="button" role="tab" @click="tab = @js($key)"
                    :aria-selected="tab === @js($key)"
                    :class="tab === @js($key) ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="-mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-medium transition">{{ $label }}</button>
        @endforeach
    </div>
    <div class="pt-5">{{ $slot }}</div>
</div>
