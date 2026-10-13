{{--
    Batch 14: carer-only client profile — today's Care Call Timeline plus
    quick links into Notes and Messages for this same person.

    Batch 15: a "Key care information" card up top — allergies, medical
    conditions, communication needs, preferences and other important
    information, straight from the service user's own profile — so it's
    the first thing a carer sees here, not something they have to go
    digging for in a care plan.

    Batch 17: wellbeing observations + handover, the same two nested
    components ShiftVisitComponent's Handover tab uses — so a carer
    reaching this person through a CareCall (not a Shift) still gets
    structured observation logging and a handover/flag-urgent route, not
    just the free-text Notes quick link below.
--}}
<div>
    <div class="mb-5 flex items-center gap-3">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-50 text-base font-semibold text-primary-600">
            {{ mb_strtoupper(mb_substr($serviceUser->name, 0, 1)) }}
        </span>
        <div class="min-w-0">
            <h1 class="truncate text-lg font-semibold text-gray-800">{{ $serviceUser->name }}</h1>
            <p class="text-sm text-gray-400">{{ now()->format('l, d M Y') }}</p>
        </div>
    </div>

    @if ($serviceUser->hasKeyCareInformation())
        <x-card hover>
            <x-slot:header>{{ __('Key care information') }}</x-slot:header>
            <div class="space-y-3 text-sm">
                @if ($serviceUser->allergies)
                    <div>
                        <span class="font-semibold text-accent-600">{{ __('Allergies') }}</span>
                        <p class="mt-0.5 text-gray-700 dark:text-gray-200">{{ $serviceUser->allergies }}</p>
                    </div>
                @endif
                @if ($serviceUser->medical_conditions)
                    <div>
                        <span class="font-semibold text-gray-600 dark:text-gray-300">{{ __('Medical conditions') }}</span>
                        <p class="mt-0.5 text-gray-700 dark:text-gray-200">{{ $serviceUser->medical_conditions }}</p>
                    </div>
                @endif
                @if ($serviceUser->communication_needs)
                    <div>
                        <span class="font-semibold text-gray-600 dark:text-gray-300">{{ __('Communication needs') }}</span>
                        <p class="mt-0.5 text-gray-700 dark:text-gray-200">{{ $serviceUser->communication_needs }}</p>
                    </div>
                @endif
                @if ($serviceUser->preferences)
                    <div>
                        <span class="font-semibold text-gray-600 dark:text-gray-300">{{ __('Preferences') }}</span>
                        <p class="mt-0.5 text-gray-700 dark:text-gray-200">{{ $serviceUser->preferences }}</p>
                    </div>
                @endif
                @if ($serviceUser->important_information)
                    <div>
                        <span class="font-semibold text-gray-600 dark:text-gray-300">{{ __('Other important information') }}</span>
                        <p class="mt-0.5 text-gray-700 dark:text-gray-200">{{ $serviceUser->important_information }}</p>
                    </div>
                @endif
            </div>
        </x-card>
    @endif

    <x-card hover class="mt-4">
        <x-slot:header>{{ __("Today's calls") }}</x-slot:header>
        <livewire:care-timeline.care-call-timeline :service-user-id="$serviceUser->id" :key="'timeline-'.$serviceUser->id" />
    </x-card>

    <div class="mt-4 space-y-4">
        <livewire:wellbeing.wellbeing-observation :service-user-id="$serviceUser->id" :key="'wellbeing-'.$serviceUser->id" />
        <livewire:handover.handover :service-user-id="$serviceUser->id" :key="'handover-'.$serviceUser->id" />
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3">
        <a href="{{ route('notes.quick') }}" wire:navigate
            class="rounded-xl border border-gray-100 bg-white p-4 text-left shadow-sm hover:border-primary-200">
            <i class="ik ik-edit-3 text-xl text-primary-500"></i>
            <div class="mt-2 text-sm font-semibold text-gray-700">{{ __('Add a note') }}</div>
        </a>
        <a href="{{ route('messages.carer') }}" wire:navigate
            class="rounded-xl border border-gray-100 bg-white p-4 text-left shadow-sm hover:border-primary-200">
            <i class="ik ik-message-square text-xl text-primary-500"></i>
            <div class="mt-2 text-sm font-semibold text-gray-700">{{ __('Messages') }}</div>
        </a>
    </div>
</div>
