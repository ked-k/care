<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Task Chart') }}" subtitle="{{ __('All active clients, one week at a time') }}"
        icon="ik ik-calendar" :breadcrumbs="['Home' => url('dashboard'), 'Tasks' => route('tasks.index'), 'Task Chart' => null]">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ \Illuminate\Support\Carbon::parse($weekStart)->format('d M') }}
                &ndash;
                {{ \Illuminate\Support\Carbon::parse($weekStart)->addDays(6)->format('d M Y') }}
            </span>
            <div class="flex items-center gap-1">
                <x-button variant="outline" size="sm" wire:click="prevWeek" title="{{ __('Previous week') }}">
                    <i class="ik ik-chevron-left"></i>
                </x-button>
                <x-button variant="outline" size="sm" wire:click="goToCurrentWeek">{{ __('This week') }}</x-button>
                <x-button variant="outline" size="sm" wire:click="nextWeek" title="{{ __('Next week') }}">
                    <i class="ik ik-chevron-right"></i>
                </x-button>
            </div>
        </div>
    </x-page-header>

    <x-card no-padding hover>
        @if ($rows->isEmpty())
            <div class="p-10">
                <x-empty-state title="{{ __('No active clients yet') }}"
                    description="{{ __('Active care plans will show up here once they exist.') }}"
                    icon="ik ik-calendar" />
            </div>
        @else
            {{-- Batch 32: sticky first column so the client name stays
                 visible while the 7 day columns scroll horizontally —
                 there's no way to make a caseload-wide week grid fit a
                 normal screen width otherwise. --}}
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                            <th class="sticky left-0 z-10 min-w-[180px] bg-white px-5 py-3 font-medium dark:bg-gray-900">
                                {{ __('Client') }}
                            </th>
                            @foreach ($days as $day)
                                @php $isToday = $day->isToday(); @endphp
                                <th class="min-w-[160px] border-l border-gray-100 px-3 py-3 text-center font-medium dark:border-gray-800 {{ $isToday ? 'bg-primary-50 text-primary-600 dark:bg-primary-500/10' : '' }}">
                                    <div>{{ $day->format('D') }}</div>
                                    <div class="text-[11px] font-normal text-gray-400">{{ $day->format('d M') }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                        @foreach ($rows as $row)
                            <tr wire:key="chart-row-{{ $row['plan']->id }}">
                                <td class="sticky left-0 z-10 min-w-[180px] bg-white px-5 py-3 align-top font-semibold text-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                    <a href="{{ route('care-plans.show', $row['plan']->id) }}" wire:navigate
                                        class="hover:text-primary-600 hover:underline">
                                        {{ $row['plan']->serviceUser->name }}
                                    </a>
                                </td>
                                @foreach ($days as $day)
                                    <td class="min-w-[160px] border-l border-gray-100 p-2 align-top dark:border-gray-800">
                                        <div class="space-y-1.5">
                                            @forelse ($row['tasksByDay']->get($day->toDateString(), collect()) as $task)
                                                <div class="rounded-md border border-gray-100 p-1.5 dark:border-gray-800"
                                                    wire:key="chart-task-{{ $task->id }}">
                                                    <div class="truncate text-xs font-medium text-gray-700 dark:text-gray-200"
                                                        title="{{ $task->title }}">
                                                        {{ $task->title }}
                                                    </div>
                                                    @if ($task->recurring_pattern)
                                                        <div class="truncate text-[10px] text-gray-400">
                                                            <i class="ik ik-refresh-cw mr-1"></i>{{ $task->recurringPatternLabel() }}
                                                        </div>
                                                    @endif
                                                    <div class="mt-0.5 flex items-center justify-between gap-1">
                                                        <span class="text-[10px] text-gray-400 whitespace-nowrap">
                                                            {{ $task->due_at?->format('H:i') ?? '—' }}
                                                        </span>
                                                        <x-badge color="{{ $task->statusBadgeColor() }}">
                                                            {{ ucfirst($task->status()) }}
                                                        </x-badge>
                                                    </div>
                                                </div>
                                            @empty
                                                <span class="block text-center text-[11px] text-gray-300">&mdash;</span>
                                            @endforelse
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</div>
