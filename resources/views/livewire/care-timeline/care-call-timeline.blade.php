{{--
    Batch 14: the Care Call Timeline. Horizontally scrollable on narrow
    screens (carer mobile shell), wraps to a plain row on wider ones (the
    manager desktop page) — same markup either way, just responsive.
--}}
<div wire:poll.30s>
    <div class="flex items-center gap-1 overflow-x-auto pb-1 sm:flex-wrap sm:overflow-visible">
        @foreach ($timeline as $i => $item)
            @php
                $color = \App\Support\CareCallStatus::badgeColor($item['status']);
                $glyph = \App\Support\CareCallStatus::glyph($item['status']);
                $glyphClasses = match ($color) {
                    'success' => 'border-green-500 bg-green-500 text-white',
                    'primary' => 'border-primary-500 bg-primary-500 text-white',
                    'warning' => 'border-amber-500 bg-amber-50 text-amber-600',
                    'danger'  => 'border-accent-500 bg-accent-50 text-accent-600',
                    'dark'    => 'border-gray-400 bg-gray-100 text-gray-500',
                    default   => 'border-gray-300 bg-white text-gray-400',
                };
            @endphp

            @if ($i > 0)
                <div class="h-0.5 w-4 shrink-0 bg-gray-200 sm:w-6"></div>
            @endif

            <div wire:key="{{ $item['key'] }}" class="flex w-24 shrink-0 flex-col items-center text-center sm:w-28">
                <span class="flex h-9 w-9 items-center justify-center rounded-full border-2 text-sm font-bold {{ $glyphClasses }}">
                    {{ $glyph }}
                </span>
                <span class="mt-1.5 text-xs font-semibold text-gray-700">{{ $item['label'] }}</span>
                <span class="text-[11px] text-gray-400">{{ $item['scheduled_start']->format('H:i') }}</span>
                <x-badge color="{{ $color }}" class="mt-1">{{ \App\Support\CareCallStatus::label($item['status']) }}</x-badge>

                @if ($item['actual_start'])
                    <span class="mt-1 text-[10px] text-gray-400">
                        {{ __('In') }} {{ $item['actual_start']->format('H:i') }}
                        @if ($item['actual_end'])
                            · {{ __('Out') }} {{ $item['actual_end']->format('H:i') }}
                        @endif
                    </span>
                @endif

                @if ($item['source'] === 'shift')
                    <a href="{{ $item['visit_route'] }}" wire:navigate
                        class="mt-1.5 rounded-full bg-primary-50 px-2.5 py-1 text-[11px] font-medium text-primary-600 hover:bg-primary-100">
                        {{ __('Open visit') }}
                    </a>
                @elseif ($isToday)
                    <div class="mt-1.5 flex flex-col items-center gap-1">
                        @if (! $item['actual_start'] && $item['status'] !== 'cancelled')
                            <button type="button" wire:click="checkIn('{{ $item['id'] }}')"
                                class="rounded-full bg-primary-50 px-2.5 py-1 text-[11px] font-medium text-primary-600 hover:bg-primary-100">
                                {{ __('Check in') }}
                            </button>
                        @elseif ($item['actual_start'] && ! $item['actual_end'])
                            <button type="button" wire:click="checkOut('{{ $item['id'] }}')"
                                class="rounded-full bg-green-50 px-2.5 py-1 text-[11px] font-medium text-green-600 hover:bg-green-100">
                                {{ __('Check out') }}
                            </button>
                        @endif

                        @if (! $isCarer)
                            <button type="button" wire:click="cancelCall('{{ $item['id'] }}')"
                                class="text-[10px] text-gray-400 hover:underline">
                                {{ $item['status'] === 'cancelled' ? __('Un-cancel') : __('Cancel') }}
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach

        @if ($timeline->isEmpty())
            <p class="py-3 text-sm text-gray-400">{{ __('No calls scheduled for this day.') }}</p>
        @endif
    </div>
</div>
