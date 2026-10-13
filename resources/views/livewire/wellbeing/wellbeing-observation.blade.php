{{--
    Batch 17: structured wellbeing observations — distinct from a free-text
    Timeline note (categorised + a severity, so a manager can scan for
    patterns) and from a full safeguarding report (this is for routine
    observations, not actual/suspected harm). Nested on both the carer's
    shift-visit screen and the standalone client-profile page.
--}}
<div>
    <x-card hover>
        <x-slot:header>
            <div class="flex items-center justify-between">
                <span>{{ __('Wellbeing observations') }}</span>
                <x-button size="sm" wire:click="openForm" @click="$dispatch('open-drawer', 'wellbeing-observation-form')">
                    <i class="ik ik-plus mr-1"></i>{{ __('Log observation') }}
                </x-button>
            </div>
        </x-slot:header>

        <div class="space-y-3">
            @forelse ($this->observations as $observation)
                <div class="rounded-lg border border-gray-100 p-3 dark:border-gray-800" wire:key="wellbeing-{{ $observation->id }}">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $observation->categoryLabel() }}</span>
                            <x-badge color="{{ $observation->severityColor() }}">{{ $observation->severityLabel() }}</x-badge>
                            @if ($observation->alert_id)
                                <x-badge color="danger">{{ __('Alert raised') }}</x-badge>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400">{{ $observation->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $observation->description }}</p>
                    @if ($observation->action_taken)
                        <p class="mt-1 text-xs text-gray-500"><span class="font-medium">{{ __('Action taken') }}:</span> {{ $observation->action_taken }}</p>
                    @endif
                    @if ($observation->follow_up_required)
                        <p class="mt-1 text-xs text-amber-600"><i class="ik ik-clock mr-1"></i>{{ __('Follow-up required') }}@if ($observation->follow_up_notes) — {{ $observation->follow_up_notes }}@endif</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-400">{{ __('by') }} {{ $observation->creator->name ?? __('a carer') }}</p>
                </div>
            @empty
                <x-empty-state title="{{ __('No observations logged yet') }}" icon="ik ik-heart" />
            @endforelse
        </div>
    </x-card>

    <x-drawer name="wellbeing-observation-form" title="{{ __('Log a wellbeing observation') }}" width="w-[28rem]">
        <div class="space-y-4">
            <x-form.select name="formCategory" label="{{ __('Category') }}" wire:model="formCategory" required>
                @foreach (\App\Models\WellbeingObservation::CATEGORIES as $value => $label)
                    <option value="{{ $value }}">{{ __($label) }}</option>
                @endforeach
            </x-form.select>

            <x-form.select name="formSeverity" label="{{ __('Severity') }}" wire:model="formSeverity" required>
                <option value="routine">{{ __('Routine') }}</option>
                <option value="concern">{{ __('Concern') }}</option>
                <option value="significant">{{ __('Significant — raises an alert') }}</option>
            </x-form.select>

            <x-form.textarea name="formDescription" label="{{ __('What was observed') }}" rows="3" wire:model="formDescription" required />
            <x-form.textarea name="formActionTaken" label="{{ __('Action taken (optional)') }}" rows="2" wire:model="formActionTaken" />
            <x-form.input name="formInformedPerson" label="{{ __('Who was informed (optional)') }}" wire:model="formInformedPerson"
                placeholder="{{ __('e.g. family, GP, manager on call') }}" />

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Photo (optional)') }}</label>
                <input type="file" wire:model="formPhoto" accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-primary-600 hover:file:bg-primary-100 dark:file:bg-primary-500/10">
                @if ($formPhoto)
                    <img src="{{ $formPhoto->temporaryUrl() }}" class="mt-2 h-20 w-20 rounded-lg object-cover">
                @endif
                @error('formPhoto')
                    <p class="mt-1 text-xs text-accent-500">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <input type="checkbox" wire:model.live="formFollowUpRequired" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                {{ __('Follow-up required') }}
            </label>

            @if ($formFollowUpRequired)
                <x-form.textarea name="formFollowUpNotes" label="{{ __('Follow-up notes') }}" rows="2" wire:model="formFollowUpNotes" required />
            @endif
        </div>

        <x-slot:footer>
            <x-button wire:click="save">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
