{{--
    Batch 17: outstanding tasks + recent handover notes for the next carer,
    plus a "flag as urgent" escalation that doesn't require going through
    safeguarding. Nested on both the shift-visit screen and client-profile
    page — see App\Livewire\Handover\HandoverComponent's class doc comment.
--}}
<div class="space-y-4">
    <x-card hover>
        <x-slot:header>{{ __('Outstanding for the next carer') }}</x-slot:header>
        <div class="space-y-2">
            @forelse ($this->outstandingTasks as $task)
                <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2 dark:border-gray-800" wire:key="handover-task-{{ $task->id }}">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-700 dark:text-gray-200">{{ $task->title }}</p>
                        @if ($task->due_at)
                            <p class="text-xs text-gray-400">{{ __('Due') }} {{ $task->due_at->format('H:i') }}</p>
                        @endif
                    </div>
                    <x-badge color="{{ $task->priority >= 4 ? 'danger' : ($task->priority >= 3 ? 'amber' : 'secondary') }}">P{{ $task->priority }}</x-badge>
                </div>
            @empty
                <x-empty-state title="{{ __('Nothing outstanding') }}" description="{{ __('All of this person\'s tasks are up to date.') }}" icon="ik ik-check-square" />
            @endforelse
        </div>
    </x-card>

    <x-card hover>
        <x-slot:header>{{ __('Handover notes') }}</x-slot:header>
        <div class="space-y-3">
            <div class="space-y-2">
                <x-form.textarea name="formHandoverNote" rows="3" wire:model="formHandoverNote"
                    placeholder="{{ __('Anything the next carer needs to know...') }}" />
                <div class="flex justify-end">
                    <x-button size="sm" wire:click="addHandoverNote">{{ __('Add handover note') }}</x-button>
                </div>
            </div>

            <div class="divide-y divide-gray-50 dark:divide-gray-800">
                @forelse ($this->handoverNotes as $note)
                    <div class="py-2" wire:key="handover-note-{{ $note->id }}">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-gray-400">{{ $note->creator->name ?? __('a carer') }}</span>
                            <span class="text-xs text-gray-400">{{ $note->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $note->content }}</p>
                    </div>
                @empty
                    <p class="py-3 text-sm text-gray-400">{{ __('No handover notes yet.') }}</p>
                @endforelse
            </div>
        </div>
    </x-card>

    <x-card class="border-accent-200 bg-accent-50/40 dark:border-accent-500/30 dark:bg-accent-500/5">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-sm text-accent-700 dark:text-accent-400">
                <i class="ik ik-flag text-lg"></i>
                <span>{{ __('Something needs attention now, but it isn\'t a safeguarding incident?') }}</span>
            </div>
            <x-button size="sm" variant="danger" @click="$dispatch('open-drawer', 'flag-urgent-form')">
                {{ __('Flag as urgent') }}
            </x-button>
        </div>
    </x-card>

    <x-drawer name="flag-urgent-form" title="{{ __('Flag as urgent') }}" width="w-[28rem]">
        <div class="space-y-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('This sends an alert straight to this person\'s named care coordinator (or a manager), without going through a safeguarding report. Use it for anything that needs a quick response.') }}
            </p>
            <x-form.textarea name="formUrgentSummary" label="{{ __('What\'s going on?') }}" rows="4" wire:model="formUrgentSummary" required />
        </div>

        <x-slot:footer>
            <x-button variant="danger" wire:click="flagUrgent">{{ __('Send alert') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
