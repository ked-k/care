@props(['rows' => 0])

@if ($rows > 0)
    {{-- Table-row skeleton --}}
    <div class="divide-y divide-gray-50">
        @for ($i = 0; $i < $rows; $i++)
            <div class="flex items-center gap-4 px-5 py-3.5">
                <div class="h-9 w-9 shrink-0 animate-pulse rounded-full bg-gray-200/70"></div>
                <div class="h-3.5 flex-1 animate-pulse rounded bg-gray-200/70"></div>
                <div class="h-3.5 w-24 animate-pulse rounded bg-gray-200/70"></div>
                <div class="h-3.5 w-16 animate-pulse rounded bg-gray-200/70"></div>
            </div>
        @endfor
    </div>
@else
    <div {{ $attributes->class(['animate-pulse rounded bg-gray-200/70', 'h-4 w-full' => ! $attributes->has('class')]) }}></div>
@endif
