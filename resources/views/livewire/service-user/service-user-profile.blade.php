<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Full Profile') }}" subtitle="{{ $serviceUser->name }}" icon="ik ik-user"
        :breadcrumbs="['Home' => url('dashboard'), 'Service Users' => route('service-users.index'), $serviceUser->name => null]">
        <div class="flex items-center gap-2 print:hidden">
            @if ($serviceUser->directionsUrl())
                <x-button variant="outline" size="sm" href="{{ $serviceUser->directionsUrl() }}" target="_blank" rel="noopener">
                    <i class="ik ik-navigation mr-1"></i>{{ __('Get directions') }}
                </x-button>
            @endif
            <x-button variant="outline" size="sm" onclick="window.print()"
                title="{{ __('Opens the print dialog — choose \'Save as PDF\' as the destination to download it.') }}">
                <i class="ik ik-printer mr-1"></i>{{ __('Print / Download PDF') }}
            </x-button>
            <a href="{{ route('service-users.index') }}" wire:navigate
                class="text-sm font-medium text-gray-500 hover:text-gray-700">{{ __('Back to list') }}</a>
        </div>
    </x-page-header>

    {{--
        Batch 37: everything below #su-profile-document is what actually
        prints — same "visibility: hidden on everything except this one
        block" trick Batch 7/21 already use for the payslip/invoice, so
        "Download PDF" (the browser's own print dialog, 'Save as PDF' as
        the destination) gives a clean one-page document instead of the
        page chrome around it.
    --}}
    <div id="su-profile-document" class="space-y-5">
        <x-card hover>
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div>
                        <x-avatar :name="$serviceUser->name" :src="$serviceUser->photoUrl()" size="h-14 w-14" class="text-base" />
                        {{-- Batch 39 --}}
                        @if ($canManage)
                            <div class="mt-1 flex items-center gap-2 text-xs print:hidden">
                                <label class="cursor-pointer font-medium text-primary-600 hover:underline">
                                    {{ __('Upload') }}
                                    <input type="file" wire:model="photoUpload" class="hidden" accept="image/*">
                                </label>
                                @if ($serviceUser->photo_path)
                                    <button type="button" wire:click="removePhoto" wire:confirm="{{ __('Remove this photo?') }}"
                                        class="font-medium text-accent-500 hover:underline">{{ __('Remove') }}</button>
                                @endif
                            </div>
                            @error('photoUpload')
                                <div class="text-xs text-red-500">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ $serviceUser->name }}</h2>
                        <p class="text-sm text-gray-400">
                            {{ $serviceUser->agency->name ?? config('app.name') }}
                            @if ($serviceUser->nhs_number)
                                <span class="mx-1">·</span>{{ __('NHS') }} {{ $serviceUser->nhs_number }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-1.5">
                    <x-badge color="{{ $serviceUser->trashed() ? 'secondary' : 'success' }}">
                        {{ $serviceUser->trashed() ? __('Inactive') : __('Active') }}
                    </x-badge>
                    <x-badge color="{{ $serviceUser->consent_status ? 'success' : 'amber' }}">
                        {{ __('Consent') }}: {{ $serviceUser->consent_status ? __('Recorded') : __('Pending') }}
                    </x-badge>
                </div>
            </div>

            <div class="mt-5 grid gap-4 border-t border-gray-100 pt-4 text-sm sm:grid-cols-2 lg:grid-cols-3 dark:border-gray-800">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Date of birth') }}</div>
                    <div class="mt-0.5 text-gray-700 dark:text-gray-200">{{ $serviceUser->dob?->format('d M Y') ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Gender') }}</div>
                    <div class="mt-0.5 text-gray-700 dark:text-gray-200">{{ $serviceUser->gender ? ucfirst($serviceUser->gender) : '—' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Care coordinator') }}</div>
                    <div class="mt-0.5 text-gray-700 dark:text-gray-200">{{ $serviceUser->careCoordinator->name ?? '—' }}</div>
                </div>
                <div class="sm:col-span-2 lg:col-span-2">
                    <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Address') }}</div>
                    <div class="mt-0.5 text-gray-700 dark:text-gray-200">{{ $serviceUser->address ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-400">{{ __('Next of kin') }}</div>
                    <div class="mt-0.5 text-gray-700 dark:text-gray-200">
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
                <div class="grid gap-4 sm:grid-cols-2">
                    @if ($serviceUser->allergies)
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-accent-600">{{ __('Allergies') }}</div>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">{{ $serviceUser->allergies }}</p>
                        </div>
                    @endif
                    @if ($serviceUser->medical_conditions)
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Medical conditions') }}</div>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">{{ $serviceUser->medical_conditions }}</p>
                        </div>
                    @endif
                    @if ($serviceUser->communication_needs)
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Communication needs') }}</div>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">{{ $serviceUser->communication_needs }}</p>
                        </div>
                    @endif
                    @if ($serviceUser->preferences)
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Preferences') }}</div>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">{{ $serviceUser->preferences }}</p>
                        </div>
                    @endif
                    @if ($serviceUser->important_information)
                        <div class="sm:col-span-2">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Other important information') }}</div>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">{{ $serviceUser->important_information }}</p>
                        </div>
                    @endif
                </div>
            </x-card>
        @endif

        <x-card no-padding hover>
            <x-slot:header>
                <div class="flex items-center justify-between">
                    <span>{{ __('Active care plan(s)') }}</span>
                    <a href="{{ route('care-plans.index') }}" wire:navigate
                        class="text-xs font-medium text-primary-600 hover:underline print:hidden">{{ __('Manage care plans') }}</a>
                </div>
            </x-slot:header>
            <div class="divide-y divide-gray-50 dark:divide-gray-800">
                @forelse ($carePlans as $plan)
                    <div class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <a href="{{ route('care-plans.show', $plan->id) }}" wire:navigate
                                    class="font-semibold text-gray-700 hover:text-primary-600 dark:text-gray-200 print:pointer-events-none print:text-gray-700">{{ $plan->title }}</a>
                                @if ($plan->summary)
                                    <p class="mt-1 text-sm text-gray-500">{{ $plan->summary }}</p>
                                @endif
                            </div>
                            @if ($plan->review_date)
                                <x-badge color="{{ $plan->review_date->isPast() ? 'danger' : 'secondary' }}">
                                    {{ __('Review') }} {{ $plan->review_date->format('d M Y') }}
                                </x-badge>
                            @endif
                        </div>

                        {{-- Batch 38: the care ROUTINE — one line per defined
                             task, not every auto-generated future occurrence
                             (see Task::scopeRoutineOnly()). A recurring task
                             shows its pattern ("Daily, 09:00") since its own
                             due_at is just when the routine started, not a
                             meaningful "next" date; a one-off task shows its
                             actual due date. For the day-by-day schedule,
                             the care plan's own Week Chart tab is the right
                             place to look — linked below. --}}
                        @if ($plan->tasks->count())
                            <ul class="mt-3 divide-y divide-gray-50 dark:divide-gray-800">
                                @foreach ($plan->tasks as $task)
                                    <li class="flex items-center justify-between gap-3 py-1.5 text-sm">
                                        <span class="text-gray-600 dark:text-gray-300">{{ $task->title }}</span>
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
                            <a href="{{ route('care-plans.show', $plan->id) }}" wire:navigate
                                class="mt-3 inline-block text-xs font-medium text-primary-600 hover:underline print:hidden">{{ __('View day-by-day schedule') }} →</a>
                        @endif

                        {{-- Batch 39: a signed PDF a manager attaches to
                             this specific plan — separate from the
                             Print/Download PDF button above, which always
                             regenerates a fresh copy of the live record. --}}
                        <div class="mt-3 flex items-center gap-2 text-xs">
                            @if ($plan->signedDocument)
                                <a href="{{ $plan->signedDocument->url() }}" target="_blank"
                                    class="font-medium text-primary-600 hover:underline">
                                    <i class="ik ik-file-text mr-1"></i>{{ __('Signed care plan on file') }}
                                </a>
                                @if ($canManage)
                                    <button type="button" wire:click="removeSignedDocument('{{ $plan->id }}')"
                                        wire:confirm="{{ __('Remove the signed document?') }}"
                                        class="text-accent-500 hover:underline print:hidden">{{ __('Remove') }}</button>
                                @endif
                            @elseif ($canManage)
                                <label class="cursor-pointer font-medium text-primary-600 hover:underline print:hidden">
                                    {{ __('Choose file…') }}
                                    <input type="file" wire:model="signedDocumentUpload" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                                </label>
                                <span wire:loading wire:target="signedDocumentUpload" class="text-gray-400">{{ __('Uploading…') }}</span>
                                <button type="button" wire:click="uploadSignedDocument('{{ $plan->id }}')"
                                    wire:loading.attr="disabled" wire:target="signedDocumentUpload"
                                    class="font-medium text-primary-600 hover:underline disabled:opacity-40 print:hidden">
                                    {{ __('Save as signed care plan') }}
                                </button>
                            @endif
                        </div>
                        @error('signedDocumentUpload')
                            <div class="text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </div>
                @empty
                    <div class="px-5 py-8">
                        <x-empty-state title="{{ __('No active care plan') }}" icon="ik ik-file-text" />
                    </div>
                @endforelse
            </div>
        </x-card>

        <div class="grid gap-5 lg:grid-cols-2">
            <x-card no-padding hover>
                <x-slot:header>
                    <div class="flex items-center justify-between">
                        <span>{{ __('Current medications') }}</span>
                        <a href="{{ route('medications.manage', $serviceUser->id) }}" wire:navigate
                            class="text-xs font-medium text-primary-600 hover:underline print:hidden">{{ __('Manage') }}</a>
                    </div>
                </x-slot:header>
                <ul class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($medications as $med)
                        <li class="px-5 py-3 text-sm">
                            <div class="font-medium text-gray-700 dark:text-gray-200">{{ $med->medication_name }}</div>
                            <div class="text-xs text-gray-400">{{ $med->dosage }} · {{ $med->frequency }}</div>
                        </li>
                    @empty
                        <li class="px-5 py-8"><x-empty-state title="{{ __('No active medications') }}" icon="ik ik-heart" /></li>
                    @endforelse
                </ul>
            </x-card>

            <x-card no-padding hover>
                <x-slot:header>
                    <div class="flex items-center justify-between">
                        <span>{{ __('Consents') }}</span>
                        <a href="{{ route('consents.manage', $serviceUser->id) }}" wire:navigate
                            class="text-xs font-medium text-primary-600 hover:underline print:hidden">{{ __('Manage') }}</a>
                    </div>
                </x-slot:header>
                <ul class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($consents as $consent)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <span class="text-gray-600 dark:text-gray-300">{{ $consent->typeLabel() }}</span>
                            <x-badge color="{{ $consent->statusColor() }}">{{ $consent->statusLabel() }}</x-badge>
                        </li>
                    @empty
                        <li class="px-5 py-8"><x-empty-state title="{{ __('No consents on file') }}" icon="ik ik-check-square" /></li>
                    @endforelse
                </ul>
            </x-card>
        </div>

        @if ($familyMembers->count())
            <x-card no-padding hover>
                <x-slot:header>
                    <div class="flex items-center justify-between">
                        <span>{{ __('Family members with portal access') }}</span>
                        <a href="{{ route('family.manage', $serviceUser->id) }}" wire:navigate
                            class="text-xs font-medium text-primary-600 hover:underline print:hidden">{{ __('Manage') }}</a>
                    </div>
                </x-slot:header>
                <ul class="divide-y divide-gray-50 dark:divide-gray-800">
                    @foreach ($familyMembers as $fm)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <span class="text-gray-600 dark:text-gray-300">{{ $fm->user->name ?? '—' }}</span>
                            <span class="text-xs text-gray-400">{{ $fm->relationship ?? '—' }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        <p class="text-xs text-gray-400 print:mt-6 print:block">
            {{ __('Generated :date by :user', ['date' => now()->format('d M Y, H:i'), 'user' => auth()->user()->name ?? '—']) }}
        </p>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            #su-profile-document, #su-profile-document * { visibility: visible; }
            #su-profile-document {
                position: absolute;
                inset: 0;
                width: 100%;
                margin: 0;
            }
        }
    </style>
</div>
