{{--
    Batch 9: tabs switch instantly via Alpine (x-show), not a Livewire
    round-trip — a phone app doesn't show a loading spinner just to switch
    screens. `@entangle('tab').live` keeps the URL (?tab=...) and the
    server-side property in sync in the background so deep-linking and the
    browser back button still work, without the click itself waiting on a
    network response. Batch 10 adds a sixth tab, Messages, backed by its
    own nested Livewire component (see the panel below for why nesting a
    child component here doesn't run into the entangle/wire:id gotcha the
    bottom nav bar's comment further down describes).
--}}
<div x-data="{ tab: @entangle('tab').live }" x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">

    {{-- Header --}}
    <div class="mb-5 flex items-center gap-3">
        <a href="{{ route('family.portal') }}" wire:navigate class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600">
            <i class="ik ik-arrow-left"></i>
        </a>
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-600 text-base font-semibold text-white">
                {{ collect(explode(' ', $serviceUser->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
            </span>
            <div>
                <h1 class="text-lg font-semibold leading-tight text-gray-800">{{ $serviceUser->name }}</h1>
                <p class="text-xs text-gray-400">{{ __('Care summary and updates') }}</p>
            </div>
        </div>
    </div>

    {{-- Desktop/tablet tab strip (hidden on phones — they get the bottom bar) --}}
    <div class="mb-5 hidden gap-1 rounded-xl bg-gray-100 p-1 sm:flex">
        @foreach ([
            'overview' => ['label' => __('Overview'), 'icon' => 'ik-home'],
            'care-plan' => ['label' => __('Care Plan'), 'icon' => 'ik-file-text'],
            'medications' => ['label' => __('Medications'), 'icon' => 'ik-heart'],
            'schedule' => ['label' => __('Schedule'), 'icon' => 'ik-calendar'],
            'notes' => ['label' => __('Notes'), 'icon' => 'ik-clock'],
            'messages' => ['label' => __('Messages'), 'icon' => 'ik-message-square'],
            // Batch 37
            'profile' => ['label' => __('Full Profile'), 'icon' => 'ik-user'],
        ] as $key => $meta)
            <button type="button" @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'bg-white text-primary-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="relative flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition">
                <i class="ik {{ $meta['icon'] }}"></i>{{ $meta['label'] }}
                @if ($key === 'messages' && $unreadMessages > 0)
                    <span class="absolute -top-1 right-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary-500 px-1 text-[10px] font-semibold text-white">{{ $unreadMessages > 9 ? '9+' : $unreadMessages }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- ===================== OVERVIEW ===================== --}}
    <div x-show="tab === 'overview'">
        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-card hover>
                    <x-slot:header>{{ __('Next visit') }}</x-slot:header>
                    @if ($nextShift)
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><i class="ik ik-calendar"></i></span>
                            <div>
                                <div class="font-semibold text-gray-700">{{ $nextShift->scheduled_start->format('D, d M · H:i') }}</div>
                                <div class="text-xs text-gray-400">{{ __('with') }} {{ $nextShift->carer->name ?? __('a carer to be confirmed') }}</div>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-400">{{ __('No upcoming visit on the published rota yet.') }}</p>
                    @endif
                </x-card>

                <x-card hover>
                    <x-slot:header>{{ __('Your care agency') }}</x-slot:header>
                    <div class="space-y-1 text-sm">
                        <p class="font-semibold text-gray-700">{{ $serviceUser->agency->name ?? config('app.name') }}</p>
                        @if ($serviceUser->agency?->phone)
                            <a href="tel:{{ $serviceUser->agency->phone }}" class="flex items-center gap-2 text-primary-600 hover:underline"><i class="ik ik-phone"></i>{{ $serviceUser->agency->phone }}</a>
                        @endif
                        @if ($serviceUser->agency?->contact_email)
                            <a href="mailto:{{ $serviceUser->agency->contact_email }}" class="flex items-center gap-2 text-primary-600 hover:underline"><i class="ik ik-mail"></i>{{ $serviceUser->agency->contact_email }}</a>
                        @endif
                    </div>
                </x-card>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <button type="button" @click="tab = 'care-plan'" class="rounded-xl border border-gray-100 bg-white p-4 text-left shadow-sm hover:border-primary-200">
                    <div class="text-2xl font-bold text-gray-800">{{ $carePlans->count() }}</div>
                    <div class="text-xs text-gray-400">{{ __('Active care plan(s)') }}</div>
                </button>
                <button type="button" @click="tab = 'medications'" class="rounded-xl border border-gray-100 bg-white p-4 text-left shadow-sm hover:border-primary-200">
                    <div class="text-2xl font-bold text-gray-800">{{ $canSeeMedications ? $medications->count() : '—' }}</div>
                    <div class="text-xs text-gray-400">{{ $canSeeMedications ? __('Current medication(s)') : __('Medications (locked)') }}</div>
                </button>
                <button type="button" @click="tab = 'notes'" class="col-span-2 rounded-xl border border-gray-100 bg-white p-4 text-left shadow-sm hover:border-primary-200 sm:col-span-1">
                    <div class="text-2xl font-bold text-gray-800">{{ $timeline->count() }}</div>
                    <div class="text-xs text-gray-400">{{ __('Recent update(s)') }}</div>
                </button>
            </div>

            {{-- Batch 37: entry point to the new Full Profile tab — not on
                 the mobile bottom bar (already a full 6 columns), so phones
                 reach it from here instead. --}}
            <button type="button" @click="tab = 'profile'"
                class="flex w-full items-center justify-between gap-3 rounded-xl border border-gray-100 bg-white p-4 text-left shadow-sm hover:border-primary-200 sm:hidden">
                <span class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><i class="ik ik-user"></i></span>
                    <span class="text-sm font-semibold text-gray-700">{{ __('Full Profile') }}</span>
                </span>
                <i class="ik ik-chevron-right text-gray-300"></i>
            </button>

            <x-card no-padding hover>
                <x-slot:header>
                    <div class="flex items-center justify-between">
                        <span>{{ __('Latest updates') }}</span>
                        <button type="button" @click="tab = 'notes'" class="text-xs font-medium text-primary-600 hover:underline">{{ __('See all') }}</button>
                    </div>
                </x-slot:header>
                <div class="divide-y divide-gray-50">
                    @forelse ($timeline->take(3) as $entry)
                        <div class="px-5 py-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-700">{{ ucfirst(str_replace('_', ' ', $entry->entry_type)) }}</span>
                                <span class="text-xs text-gray-400">{{ $entry->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ $entry->content }}</p>
                        </div>
                    @empty
                        <div class="px-5 py-8">
                            <x-empty-state title="{{ __('No updates yet') }}" icon="ik ik-clock" />
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>

    {{-- ===================== CARE PLAN / PROGRAMS ===================== --}}
    <div x-show="tab === 'care-plan'">
        <div class="space-y-4">
            @forelse ($carePlans as $plan)
                <x-card hover>
                    <div class="mb-2 flex items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold text-gray-800">{{ $plan->title }}</div>
                            @if ($plan->summary)
                                <p class="mt-1 text-sm text-gray-500">{{ $plan->summary }}</p>
                            @endif
                        </div>
                        @if ($plan->review_date)
                            <x-badge color="{{ $plan->review_date->isPast() ? 'danger' : 'gray' }}">{{ __('Review') }} {{ $plan->review_date->format('d M') }}</x-badge>
                        @endif
                    </div>

                    @php $goals = $plan->plan_data['goals'] ?? []; @endphp
                    @if (count($goals))
                        <div class="mt-3 border-t border-gray-50 pt-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Goals') }}</p>
                            <ul class="space-y-1.5">
                                @foreach ($goals as $goal)
                                    <li class="flex items-start justify-between gap-2 text-sm">
                                        <span class="text-gray-600">{{ $goal['description'] ?? '' }}</span>
                                        <x-badge color="{{ ($goal['status'] ?? '') === 'Achieved' ? 'success' : 'gray' }}">{{ $goal['status'] ?? __('In progress') }}</x-badge>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @php $routine = $plan->plan_data['daily_routine'] ?? []; @endphp
                    @if (count($routine))
                        <div class="mt-3 border-t border-gray-50 pt-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Daily routine') }}</p>
                            <ul class="space-y-1 text-sm text-gray-600">
                                @foreach (collect($routine)->sortBy('time') as $item)
                                    <li><span class="font-mono text-xs text-gray-400">{{ $item['time'] ?? '' }}</span> — {{ $item['task'] ?? '' }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Batch 38: $plan->tasks is now scoped to routineOnly()
                         (one row per defined task, not every auto-generated
                         future occurrence — see Task::scopeRoutineOnly()).
                         A recurring task's own status/due_at reflect only
                         its very first occurrence, which is stale and
                         misleading once the chain has moved on (it would
                         show "Completed" forever even once today's visit
                         hasn't happened yet) — so a recurring row shows its
                         pattern instead of a badge; a one-off task's status
                         is still accurate and keeps the badge. --}}
                    @if (count($plan->tasks))
                        <div class="mt-3 border-t border-gray-50 pt-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Care tasks') }}</p>
                            <ul class="divide-y divide-gray-50">
                                @foreach ($plan->tasks as $task)
                                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                        <div>
                                            <div class="font-medium text-gray-700">{{ $task->title }}</div>
                                            @if ($task->recurring_pattern)
                                                <div class="text-xs text-gray-400">{{ $task->recurringPatternLabel() }}{{ $task->due_at ? ', '.$task->due_at->format('H:i') : '' }}</div>
                                            @elseif ($task->due_at)
                                                <div class="text-xs text-gray-400">{{ $task->due_at->format('D d M, H:i') }}</div>
                                            @endif
                                        </div>
                                        @unless ($task->recurring_pattern)
                                            @php
                                                $status = $task->status();
                                                $color = match ($status) {
                                                    'completed' => 'success',
                                                    'overdue', 'refused' => 'danger',
                                                    'skipped' => 'gray',
                                                    default => 'primary',
                                                };
                                            @endphp
                                            <x-badge color="{{ $color }}">{{ ucfirst($status) }}</x-badge>
                                        @endunless
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </x-card>
            @empty
                <x-empty-state title="{{ __('No active care plan to show yet') }}" icon="ik ik-file-text" />
            @endforelse
        </div>
    </div>

    {{-- ===================== MEDICATIONS ===================== --}}
    <div x-show="tab === 'medications'">
        <div class="space-y-4">
            @if (! $canSeeMedications)
                <x-card>
                    <div class="flex flex-col items-center gap-3 py-6 text-center">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-500"><i class="ik ik-lock text-xl"></i></span>
                        <p class="font-semibold text-gray-700">{{ __('Medication updates aren\'t shared yet') }}</p>
                        <p class="max-w-sm text-sm text-gray-500">{{ __('Sharing medication details with family requires consent on file for this person. Ask the care team to enable "consent for medication-related communication" if you\'d like to see this.') }}</p>
                    </div>
                </x-card>
            @else
                @forelse ($medications as $med)
                    <x-card hover>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold text-gray-800">{{ $med->medication_name }}</div>
                                <div class="text-sm text-gray-500">{{ $med->dosage }} · {{ ucfirst($med->administration_route ?? '—') }}</div>
                            </div>
                            @if ($med->is_prn)
                                <x-badge color="secondary">{{ __('As needed (PRN)') }}</x-badge>
                            @elseif ($med->scheduledTimeFormatted())
                                <x-badge color="primary">{{ $med->scheduledTimeFormatted() }}</x-badge>
                            @endif
                        </div>
                        @if ($med->instructions)
                            <p class="mt-2 text-sm text-gray-500">{{ $med->instructions }}</p>
                        @endif

                        @if ($med->administrations->isNotEmpty())
                            <div class="mt-3 border-t border-gray-50 pt-3">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Recent activity') }}</p>
                                <ul class="space-y-1.5">
                                    @foreach ($med->administrations as $admin)
                                        @php
                                            $color = match ($admin->status) {
                                                'given' => 'success',
                                                'prompted' => 'primary',
                                                'refused' => 'danger',
                                                'missed' => 'danger',
                                                default => 'gray',
                                            };
                                        @endphp
                                        <li class="flex items-center justify-between text-sm">
                                            <span class="text-gray-500">{{ $admin->scheduled_time->format('d M, H:i') }}</span>
                                            <x-badge color="{{ $color }}">{{ ucfirst($admin->status) }}</x-badge>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </x-card>
                @empty
                    <x-empty-state title="{{ __('No active medications on file') }}" icon="ik ik-heart" />
                @endforelse
            @endif
        </div>
    </div>

    {{-- ===================== SCHEDULE ===================== --}}
    <div x-show="tab === 'schedule'">
        <div class="space-y-4">
            <x-card no-padding hover>
                <x-slot:header>{{ __('Upcoming visits') }}</x-slot:header>
                <div class="divide-y divide-gray-50">
                    @forelse ($upcomingShifts as $shift)
                        <div class="flex items-center gap-3 px-5 py-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><i class="ik ik-calendar"></i></span>
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-gray-700">{{ $shift->scheduled_start->format('D, d M · H:i') }} – {{ $shift->scheduled_end->format('H:i') }}</div>
                                <div class="text-xs text-gray-400">{{ __('with') }} {{ $shift->carer->name ?? __('carer to be confirmed') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8">
                            <x-empty-state title="{{ __('No upcoming visits published yet') }}" icon="ik ik-calendar" />
                        </div>
                    @endforelse
                </div>
            </x-card>

            <x-card no-padding hover>
                <x-slot:header>{{ __('Recent visits') }}</x-slot:header>
                <div class="divide-y divide-gray-50">
                    @forelse ($recentShifts as $shift)
                        <div class="flex items-center gap-3 px-5 py-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500"><i class="ik ik-calendar"></i></span>
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-gray-700">{{ $shift->scheduled_start->format('D, d M · H:i') }}</div>
                                <div class="text-xs text-gray-400">{{ __('with') }} {{ $shift->carer->name ?? '—' }}</div>
                            </div>
                            <x-badge color="{{ $shift->actual_start ? 'success' : 'gray' }}">{{ $shift->actual_start ? __('Visited') : __('Scheduled') }}</x-badge>
                        </div>
                    @empty
                        <div class="px-5 py-8">
                            <x-empty-state title="{{ __('No past visits to show yet') }}" icon="ik ik-clock" />
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>

    {{-- ===================== NOTES ===================== --}}
    <div x-show="tab === 'notes'">
        <x-card no-padding hover>
            <x-slot:header>{{ __('Care timeline') }}</x-slot:header>
            <div class="divide-y divide-gray-50">
                @forelse ($timeline as $entry)
                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">{{ ucfirst(str_replace('_', ' ', $entry->entry_type)) }}</span>
                            <span class="text-xs text-gray-400">{{ $entry->created_at->format('d M Y, H:i') }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">{{ $entry->content }}</p>
                        <p class="mt-1 text-xs text-gray-400">{{ __('by') }} {{ $entry->creator->name ?? __('a carer') }}</p>
                    </div>
                @empty
                    <div class="px-5 py-10">
                        <x-empty-state title="{{ __('No updates yet') }}"
                            description="{{ __('Updates appear here as carers complete visits.') }}" icon="ik ik-clock" />
                    </div>
                @endforelse
            </div>
        </x-card>
    </div>

    {{-- ===================== MESSAGES ===================== --}}
    <div x-show="tab === 'messages'">
        {{-- A real nested Livewire component (own wire:id), not stack-pushed
             content — safe to mount here even though it's hidden by
             x-show until this tab is opened. Registered name keeps the
             "Component" suffix, kebab-cased, matching how this app's other
             embedded component is invoked (@livewire('messaging.chat-drawer-component')
             in include/header.blade.php) — Livewire's auto-discovery
             doesn't strip that suffix the way a tag name might suggest. --}}
        @livewire('family.family-chat-component', ['serviceUserId' => $serviceUserId], key('family-chat-'.$serviceUserId))
    </div>

    {{-- ===================== FULL PROFILE ===================== --}}
    {{-- Batch 37: the family-safe version of the same consolidated report
         staff get on ServiceUserProfileComponent — basic details, key care
         info, next of kin, agency contact, and the active care plan's
         goals/tasks (already shown on the Care Plan tab above, repeated
         here so the printed document is self-contained). Deliberately
         leaves out anything staff-only: billing/payer info, the named care
         coordinator, and the Consents list — none of that is shown to
         family anywhere else in this portal either, so this report
         doesn't newly expose it. --}}
    <div x-show="tab === 'profile'">
        <div id="family-profile-document" class="space-y-4">
            <div class="mb-2 flex items-center justify-end gap-2 print:hidden">
                @if ($serviceUser->directionsUrl())
                    <a href="{{ $serviceUser->directionsUrl() }}" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="ik ik-navigation"></i>{{ __('Get directions') }}
                    </a>
                @endif
                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    title="{{ __('Opens the print dialog — choose \'Save as PDF\' as the destination to download it.') }}">
                    <i class="ik ik-printer"></i>{{ __('Print / Download PDF') }}
                </button>
            </div>

            <x-card hover>
                <div class="flex items-center gap-3">
                    {{-- Batch 39: photo if one's been uploaded (staff-side,
                         read-only here), initials otherwise. --}}
                    @if ($serviceUser->photoUrl())
                        <img src="{{ $serviceUser->photoUrl() }}" alt="{{ $serviceUser->name }}"
                            class="h-12 w-12 shrink-0 rounded-full object-cover">
                    @else
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-600 text-sm font-semibold text-white">
                            {{ collect(explode(' ', $serviceUser->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
                        </span>
                    @endif
                    <div>
                        <div class="font-semibold text-gray-800">{{ $serviceUser->name }}</div>
                        <div class="text-xs text-gray-400">{{ $serviceUser->agency->name ?? config('app.name') }}</div>
                    </div>
                </div>
                <div class="mt-4 grid gap-3 border-t border-gray-100 pt-4 text-sm sm:grid-cols-2">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Date of birth') }}</div>
                        <div class="mt-0.5 text-gray-700">{{ $serviceUser->dob?->format('d M Y') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Address') }}</div>
                        <div class="mt-0.5 text-gray-700">{{ $serviceUser->address ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Next of kin') }}</div>
                        <div class="mt-0.5 text-gray-700">
                            {{ $serviceUser->next_of_kin_name ?? '—' }}
                            @if ($serviceUser->next_of_kin_contact)
                                <span class="text-gray-400">· {{ $serviceUser->next_of_kin_contact }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </x-card>

            @if ($serviceUser->hasKeyCareInformation())
                <x-card hover>
                    <x-slot:header>{{ __('Key care information') }}</x-slot:header>
                    <div class="grid gap-3 sm:grid-cols-2 text-sm">
                        @if ($serviceUser->allergies)
                            <div><span class="font-semibold text-accent-600">{{ __('Allergies') }}</span><p class="mt-0.5 text-gray-700">{{ $serviceUser->allergies }}</p></div>
                        @endif
                        @if ($serviceUser->medical_conditions)
                            <div><span class="font-semibold text-gray-600">{{ __('Medical conditions') }}</span><p class="mt-0.5 text-gray-700">{{ $serviceUser->medical_conditions }}</p></div>
                        @endif
                        @if ($serviceUser->communication_needs)
                            <div><span class="font-semibold text-gray-600">{{ __('Communication needs') }}</span><p class="mt-0.5 text-gray-700">{{ $serviceUser->communication_needs }}</p></div>
                        @endif
                        @if ($serviceUser->preferences)
                            <div><span class="font-semibold text-gray-600">{{ __('Preferences') }}</span><p class="mt-0.5 text-gray-700">{{ $serviceUser->preferences }}</p></div>
                        @endif
                    </div>
                </x-card>
            @endif

            @if ($carePlans->count())
                <x-card hover>
                    <x-slot:header>{{ __('Active care plan') }}</x-slot:header>
                    @foreach ($carePlans as $plan)
                        <div class="{{ ! $loop->first ? 'mt-4 border-t border-gray-100 pt-4' : '' }}">
                            <div class="font-semibold text-gray-700">{{ $plan->title }}</div>
                            @if ($plan->summary)
                                <p class="mt-1 text-sm text-gray-500">{{ $plan->summary }}</p>
                            @endif
                            {{-- Batch 38: routine, not every occurrence — see the
                                 identical note on the Care Plan tab above. --}}
                            @if ($plan->tasks->count())
                                <ul class="mt-2 divide-y divide-gray-50">
                                    @foreach ($plan->tasks as $task)
                                        <li class="flex items-center justify-between gap-3 py-1.5 text-sm">
                                            <span class="text-gray-600">{{ $task->title }}</span>
                                            <span class="text-xs text-gray-400">
                                                @if ($task->recurring_pattern)
                                                    {{ $task->recurringPatternLabel() }}{{ $task->due_at ? ', '.$task->due_at->format('H:i') : '' }}
                                                @else
                                                    {{ $task->due_at?->format('d M, H:i') ?? '—' }}
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            {{-- Batch 39: read-only here — family can view/
                                 download a signed care plan a manager has
                                 attached, not upload one themselves. --}}
                            @if ($plan->signedDocument)
                                <a href="{{ $plan->signedDocument->url() }}" target="_blank"
                                    class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:underline">
                                    <i class="ik ik-file-text"></i>{{ __('Signed care plan') }}
                                </a>
                            @endif
                        </div>
                    @endforeach
                </x-card>
            @endif

            <p class="text-xs text-gray-400">{{ __('Generated :date', ['date' => now()->format('d M Y, H:i')]) }}</p>
        </div>

        <style>
            @media print {
                body * { visibility: hidden; }
                #family-profile-document, #family-profile-document * { visibility: visible; }
                #family-profile-document { position: absolute; inset: 0; width: 100%; margin: 0; }
            }
        </style>
    </div>

    {{-- Mobile bottom tab bar. Deliberately kept INSIDE this component's
         single root element (not pushed to a layout stack) — Livewire only
         gives this component one wire:id, carried by the wrapping element
         opening this file, and Alpine's entangle resolves against the
         nearest wire:id ancestor. A stack-pushed nav would render as a
         sibling of that root rather than a descendant, so it would have no
         component to bind to. Fixed positioning doesn't require being a
         direct child of the page body to dock to the viewport, so nesting
         it here costs nothing. --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-100 bg-white/95 backdrop-blur sm:hidden"
        style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="mx-auto grid max-w-3xl grid-cols-6">
            @foreach ([
                'overview' => ['label' => __('Home'), 'icon' => 'ik-home'],
                'care-plan' => ['label' => __('Plan'), 'icon' => 'ik-file-text'],
                'medications' => ['label' => __('Meds'), 'icon' => 'ik-heart'],
                'schedule' => ['label' => __('Visits'), 'icon' => 'ik-calendar'],
                'notes' => ['label' => __('Notes'), 'icon' => 'ik-clock'],
                'messages' => ['label' => __('Chat'), 'icon' => 'ik-message-square'],
            ] as $key => $meta)
                <button type="button" @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'text-primary-600' : 'text-gray-400'"
                    class="relative flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-medium">
                    <i class="ik {{ $meta['icon'] }} text-lg"></i>
                    {{ $meta['label'] }}
                    @if ($key === 'messages' && $unreadMessages > 0)
                        <span class="absolute right-3 top-1 flex h-3.5 min-w-3.5 items-center justify-center rounded-full bg-primary-500 px-1 text-[9px] font-semibold text-white">{{ $unreadMessages > 9 ? '9+' : $unreadMessages }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </nav>
</div>
