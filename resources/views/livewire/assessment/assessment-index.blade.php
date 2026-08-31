<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Assessments') }}" subtitle="{{ __('for :name', ['name' => $serviceUser->name]) }}"
        icon="ik ik-clipboard" :breadcrumbs="['Home' => url('dashboard'), 'Service Users' => route('service-users.index'), 'Assessments' => null]">
        <x-button variant="primary" size="sm" wire:click="openCreateForm" @click="$dispatch('open-drawer', 'assessment-form')">
            <i class="ik ik-plus mr-1"></i>{{ __('New assessment') }}
        </x-button>
    </x-page-header>

    <div class="space-y-4">
        @forelse ($assessments as $assessment)
            <x-card no-padding hover>
                <div class="p-5">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-700 dark:text-gray-200">{{ $assessment->typeLabel() }}</div>
                            <div class="text-xs text-gray-400">
                                {{ __('Conducted by') }} {{ $assessment->conductedBy->name ?? '—' }}
                                · {{ $assessment->created_at->format('d M Y') }}
                                @if ($assessment->review_date)
                                    · {{ __('Review due') }} {{ $assessment->review_date->format('d M Y') }}
                                    @if ($assessment->isDue())
                                        <span class="text-accent-500 font-medium">({{ __('overdue') }})</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-badge color="{{ $assessment->riskColor() }}">{{ ucfirst($assessment->risk_level) }} {{ __('risk') }}</x-badge>
                            @if ($assessment->score !== null)
                                <x-badge color="secondary">{{ __('Score') }}: {{ $assessment->score }}</x-badge>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 space-y-2 border-t border-gray-50 pt-4 dark:border-gray-800">
                        @foreach ($assessment->questions_and_answers as $qa)
                            <div>
                                <div class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $qa['question'] ?? '' }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $qa['answer'] ?? '—' }}</div>
                            </div>
                        @endforeach
                    </div>

                    @if ($assessment->recommendations)
                        <div class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-600 dark:bg-gray-800/40 dark:text-gray-300">
                            <span class="font-medium">{{ __('Recommendations:') }}</span> {{ $assessment->recommendations }}
                        </div>
                    @endif
                </div>
            </x-card>
        @empty
            <x-card>
                <x-empty-state title="{{ __('No assessments recorded yet') }}"
                    description="{{ __('Record an initial assessment or a specific risk assessment for this service user.') }}"
                    icon="ik ik-clipboard" />
            </x-card>
        @endforelse
    </div>

    <x-drawer name="assessment-form" title="{{ __('New assessment') }}" width="w-[34rem]">
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <x-form.select name="formAssessmentType" label="{{ __('Assessment type') }}" wire:model="formAssessmentType">
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </x-form.select>
                <x-form.select name="formRiskLevel" label="{{ __('Risk level') }}" wire:model="formRiskLevel">
                    <option value="low">{{ __('Low') }}</option>
                    <option value="medium">{{ __('Medium') }}</option>
                    <option value="high">{{ __('High') }}</option>
                </x-form.select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Questions & answers') }}</label>
                <div class="space-y-2">
                    @foreach ($formQuestions as $index => $row)
                        <div class="flex items-start gap-2" wire:key="qa-row-{{ $index }}">
                            <div class="flex-1 space-y-1">
                                <input type="text" wire:model="formQuestions.{{ $index }}.question" placeholder="{{ __('Question') }}"
                                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                                <input type="text" wire:model="formQuestions.{{ $index }}.answer" placeholder="{{ __('Answer') }}"
                                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800">
                            </div>
                            <button type="button" wire:click="removeQuestionRow({{ $index }})"
                                class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-accent-500">
                                <i class="ik ik-trash-2 text-sm"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
                @error('formQuestions') <p class="mt-1 text-xs text-accent-500">{{ $message }}</p> @enderror
                <button type="button" wire:click="addQuestionRow" class="mt-2 text-sm font-medium text-primary-600 hover:underline">
                    <i class="ik ik-plus mr-1"></i>{{ __('Add question') }}
                </button>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="number" name="formScore" label="{{ __('Score (optional)') }}" wire:model="formScore" />
                <x-form.input type="date" name="formReviewDate" label="{{ __('Next review date (optional)') }}" wire:model="formReviewDate" />
            </div>

            <x-form.textarea name="formRecommendations" label="{{ __('Recommendations (optional)') }}" rows="3"
                wire:model="formRecommendations" />
        </div>
        <x-slot:footer>
            <x-button wire:click="saveAssessment">{{ __('Save assessment') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
