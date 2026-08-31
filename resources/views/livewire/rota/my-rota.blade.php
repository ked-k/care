<div>
    <x-page-header title="{{ __('My Rota') }}" subtitle="{{ __('Your upcoming published shifts') }}"
        icon="ik ik-calendar" :breadcrumbs="['Home' => url('dashboard'), 'My Rota' => null]">
        <div class="flex items-center gap-2">
            <x-button variant="outline" size="sm" wire:click="previousWeek">
                <i class="ik ik-chevron-left"></i>
            </x-button>
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">
                {{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}
            </span>
            <x-button variant="outline" size="sm" wire:click="nextWeek">
                <i class="ik ik-chevron-right"></i>
            </x-button>
            <x-button variant="outline" size="sm" wire:click="goToday">{{ __('Today') }}</x-button>
        </div>
    </x-page-header>

    <div class="space-y-4">
        @foreach ($days as $date)
            @php $dayShifts = $shiftsByDate->get($date->toDateString(), collect()); @endphp
            <x-card no-padding hover>
                <x-slot:header>
                    <div class="flex items-center justify-between">
                        <span class="font-semibold {{ $date->isToday() ? 'text-primary-600' : '' }}">
                            {{ $date->format('l, d M Y') }}
                            @if ($date->isToday())
                                <x-badge color="primary">{{ __('Today') }}</x-badge>
                            @endif
                        </span>
                        <span class="text-xs text-gray-400">{{ $dayShifts->count() }} {{ __('shift(s)') }}</span>
                    </div>
                </x-slot:header>

                @forelse ($dayShifts as $shift)
                    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-50 last:border-b-0 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <x-badge color="{{ $shift->shift_type === 'night' ? 'secondary' : 'primary' }}">
                                {{ $shift->shift_type === 'night' ? __('Night') : __('Day') }}
                            </x-badge>
                            <div>
                                <div class="font-semibold text-gray-700 dark:text-gray-200">
                                    {{ $shift->serviceUser->name ?? __('Unassigned service user') }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    {{ $shift->scheduled_start->format('H:i') }} – {{ $shift->scheduled_end->format('H:i') }}
                                    @if ($shift->break_minutes)
                                        · {{ __(':min min break', ['min' => $shift->break_minutes]) }}
                                    @endif
                                </div>
                                @if ($shift->notes)
                                    <div class="text-xs text-gray-400">{{ $shift->notes }}</div>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('tasks.by-shift', $shift->id) }}" wire:navigate
                            class="text-primary-600 hover:underline text-sm font-medium whitespace-nowrap">
                            {{ __('View tasks') }}
                        </a>
                    </div>
                @empty
                    <div class="px-5 py-6 text-sm text-gray-400">{{ __('No shifts scheduled.') }}</div>
                @endforelse
            </x-card>
        @endforeach
    </div>

    <p class="mt-4 text-xs text-gray-400">
        {{ __('Only published rotas appear here. If a week looks empty, your manager may not have published it yet.') }}
    </p>
</div>
