<div>
    <x-page-header title="{{ __('My Rota') }}" subtitle="{{ __('Your upcoming published shifts') }}"
        icon="ik ik-calendar" :breadcrumbs="['Home' => url('dashboard'), 'My Rota' => null]">
        <div class="flex flex-wrap items-center gap-2">
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
                    {{-- Batch 11: the whole row is now a tap target linking
                         straight into the single-screen shift visit (check
                         in/out, tasks, meds, notes) — easier to hit on a
                         phone than a small text link off to the side. --}}
                    <a href="{{ route('rota.visit', $shift->id) }}" wire:navigate
                        class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-50 last:border-b-0 hover:bg-gray-50 active:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-800/60">
                        <div class="flex items-center gap-3 min-w-0">
                            <x-badge color="{{ $shift->shift_type === 'night' ? 'secondary' : 'primary' }}">
                                {{ $shift->shift_type === 'night' ? __('Night') : __('Day') }}
                            </x-badge>
                            <div class="min-w-0">
                                <div class="font-semibold text-gray-700 dark:text-gray-200 truncate">
                                    {{ $shift->serviceUser->name ?? __('Unassigned service user') }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    {{ $shift->scheduled_start->format('H:i') }} – {{ $shift->scheduled_end->format('H:i') }}
                                    @if ($shift->break_minutes)
                                        · {{ __(':min min break', ['min' => $shift->break_minutes]) }}
                                    @endif
                                </div>
                                @if ($shift->notes)
                                    <div class="text-xs text-gray-400 truncate">{{ $shift->notes }}</div>
                                @endif
                            </div>
                        </div>
                        <span class="shrink-0 flex items-center gap-1 text-primary-600 text-sm font-medium whitespace-nowrap">
                            {{ __('Open visit') }}
                            <i class="ik ik-chevron-right"></i>
                        </span>
                    </a>
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
