<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Compliance Dashboard') }}" subtitle="{{ __('Continuous visibility, not just an audit-time scramble') }}"
        icon="ik ik-check-square" :breadcrumbs="['Home' => url('dashboard'), 'Compliance' => null]" />

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        @php
            $tiles = [
                ['label' => 'Care Plans Due for Review', 'value' => $metrics['care_plans_due']],
                ['label' => 'Overdue Reviews', 'value' => $metrics['overdue_reviews'], 'danger' => $metrics['overdue_reviews'] > 0],
                ['label' => 'Training Compliance', 'value' => $metrics['training_compliance'] !== null ? $metrics['training_compliance'].'%' : '—'],
                ['label' => 'Medication Compliance', 'value' => $metrics['medication_compliance'] !== null ? $metrics['medication_compliance'].'%' : '—'],
                ['label' => 'Open Safeguarding Cases', 'value' => $metrics['open_safeguarding'], 'danger' => $metrics['open_safeguarding'] > 0],
                ['label' => 'Missed Visits (this week)', 'value' => $metrics['missed_visits'], 'danger' => $metrics['missed_visits'] > 0],
                ['label' => 'Pending Policy Acknowledgments', 'value' => $metrics['pending_acknowledgments']],
                ['label' => 'Open Data Incidents', 'value' => $metrics['open_data_incidents'], 'danger' => $metrics['open_data_incidents'] > 0],
            ];
        @endphp
        @foreach ($tiles as $tile)
            <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-xs uppercase tracking-wide text-gray-400">{{ __($tile['label']) }}</div>
                <div class="mt-1 text-2xl font-semibold {{ ($tile['danger'] ?? false) ? 'text-accent-500' : 'text-gray-800 dark:text-gray-100' }}">
                    {{ $tile['value'] }}
                </div>
            </div>
        @endforeach
    </div>

    <p class="mb-6 text-xs text-gray-400">
        {{ __('Training compliance is a simple completed-vs-started ratio — the schema has no "mandatory training" flag yet, so this is not a true regulatory mandatory-training figure. Missed visits relies on shifts explicitly marked "missed", which nothing currently automates.') }}
    </p>

    <x-card no-padding hover>
        <x-slot:header>
            <div class="flex items-center justify-between">
                <span>{{ __('Compliance checklist') }}</span>
                @if ($canManage)
                    <x-button variant="outline" size="sm" wire:click="openCreateForm"
                        @click="$dispatch('open-drawer', 'compliance-check-form')">
                        <i class="ik ik-plus mr-1"></i>{{ __('Add check') }}
                    </x-button>
                @endif
            </div>
        </x-slot:header>
        <div class="divide-y divide-gray-50 dark:divide-gray-800">
            @forelse ($checks as $check)
                <div class="flex items-center justify-between px-5 py-3" wire:key="check-{{ $check->id }}">
                    <div>
                        <div class="font-semibold text-gray-700 dark:text-gray-200">{{ $check->category }}</div>
                        @if ($check->next_due_at)
                            <div class="text-xs text-gray-400">{{ __('Due') }} {{ $check->next_due_at->format('d M Y') }}</div>
                        @endif
                        @if ($check->notes)
                            <div class="text-xs text-gray-400">{{ $check->notes }}</div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <x-badge color="{{ $check->statusColor() }}">
                            {{ $check->isOverdue() ? __('Overdue') : ucfirst(str_replace('_', ' ', $check->status)) }}
                        </x-badge>
                        @if ($canManage)
                            <button type="button" wire:click="advanceCheck('{{ $check->id }}')"
                                class="text-primary-600 hover:underline text-sm font-medium">{{ __('Advance') }}</button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-5 py-10">
                    <x-empty-state title="{{ __('No compliance checks tracked yet') }}"
                        description="{{ __('Add manual checklist items like a CQC registration renewal.') }}" icon="ik ik-check-square" />
                </div>
            @endforelse
        </div>
    </x-card>

    <x-drawer name="compliance-check-form" title="{{ __('Add compliance check') }}">
        <div class="space-y-4">
            <x-form.input name="formCategory" label="{{ __('Category') }}" wire:model="formCategory" required
                placeholder="{{ __('e.g. CQC registration renewal') }}" />
            <x-form.input type="date" name="formNextDueAt" label="{{ __('Next due (optional)') }}" wire:model="formNextDueAt" />
            <x-form.textarea name="formNotes" label="{{ __('Notes (optional)') }}" rows="3" wire:model="formNotes" />
        </div>
        <x-slot:footer>
            <x-button wire:click="saveCheck">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
