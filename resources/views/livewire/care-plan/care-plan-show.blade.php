<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ $plan->title }}" subtitle="{{ $plan->serviceUser->name ?? '' }}" icon="ik ik-clipboard"
        :breadcrumbs="['Home' => url('dashboard'), 'Care Plans' => route('care-plans.index'), $plan->title => null]">
        <div class="flex items-center gap-2">
            <x-badge color="{{ $plan->is_active ? 'success' : 'secondary' }}">
                {{ $plan->is_active ? __('Active') : __('Inactive') }}
            </x-badge>
            <x-button variant="outline" size="sm" wire:click="toggleActive">
                {{ $plan->is_active ? __('Deactivate') : __('Reactivate') }}
            </x-button>
            <x-button variant="primary" size="sm" wire:click="openCreateTaskForm"
                @click="$dispatch('open-drawer', 'task-form')">
                <i class="ik ik-plus mr-1"></i>{{ __('New task') }}
            </x-button>
        </div>
    </x-page-header>

    @if ($plan->summary)
        <x-card hover class="mb-5">
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $plan->summary }}</p>
        </x-card>
    @endif

    <x-card no-padding hover>
        <x-slot:header>{{ __('Tasks') }}</x-slot:header>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Task') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Assigned To') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Due') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Priority') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Requires') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($tasks as $task)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="task-{{ $task->id }}">
                            <td class="px-5 py-3">
                                <div class="font-semibold text-gray-700 dark:text-gray-200">{{ $task->title }}</div>
                                @if ($task->recurring_pattern)
                                    <div class="text-xs text-gray-400"><i
                                            class="ik ik-refresh-cw mr-1"></i>{{ ucfirst(str_replace('_', ' ', $task->recurring_pattern)) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $task->assignee->name ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $task->due_at?->format('d M, H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <x-badge
                                    color="{{ $task->priority >= 4 ? 'danger' : ($task->priority >= 3 ? 'amber' : 'secondary') }}">
                                    P{{ $task->priority }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-gray-400 text-xs">
                                @if ($task->requires_photo)
                                    <i class="ik ik-camera mr-1" title="{{ __('Photo required') }}"></i>
                                @endif
                                @if ($task->requires_signature)
                                    <i class="ik ik-edit-3" title="{{ __('Signature required') }}"></i>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @php $status = $task->status(); @endphp
                                <x-badge
                                    color="{{ match ($status) {
                                        'completed' => 'success',
                                        'refused' => 'danger',
                                        'skipped' => 'secondary',
                                        'overdue' => 'danger',
                                        default => 'primary',
                                    } }}">
                                    {{ ucfirst($status) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-right space-x-2 whitespace-nowrap">
                                <button type="button" wire:click="openEditTaskForm('{{ $task->id }}')"
                                    @click="$dispatch('open-drawer', 'task-form')"
                                    class="text-primary-600 hover:underline text-sm font-medium">{{ __('Edit') }}</button>
                                <button type="button" wire:click="deleteTask('{{ $task->id }}')"
                                    wire:confirm="{{ __('Remove this task?') }}"
                                    class="text-accent-500 hover:underline text-sm font-medium">{{ __('Remove') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10">
                                <x-empty-state title="{{ __('No tasks yet') }}"
                                    description="{{ __('Add tasks for carers to complete during their visits.') }}"
                                    icon="ik ik-check-square" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-drawer name="task-form" title="{{ $editingTaskId ? __('Edit task') : __('New task') }}" width="w-[30rem]">
        <div class="space-y-4">
            <x-form.input name="formTitle" label="{{ __('Title') }}" wire:model="formTitle" required
                placeholder="{{ __('e.g. Administer morning medication') }}" />
            <x-form.textarea name="formDescription" label="{{ __('Description (optional)') }}" rows="2"
                wire:model="formDescription" />

            <div class="grid grid-cols-2 gap-3">
                <x-form.select name="formType" label="{{ __('Type') }}" wire:model="formType">
                    <option value="general">{{ __('General') }}</option>
                    <option value="personal_care">{{ __('Personal Care') }}</option>
                    <option value="medication">{{ __('Medication') }}</option>
                    <option value="meal">{{ __('Meal') }}</option>
                    <option value="mobility">{{ __('Mobility') }}</option>
                    <option value="wellbeing_check">{{ __('Wellbeing Check') }}</option>
                </x-form.select>
                <x-form.select name="formPriority" label="{{ __('Priority') }}" wire:model="formPriority">
                    <option value="1">{{ __('P1 — Low') }}</option>
                    <option value="2">{{ __('P2') }}</option>
                    <option value="3">{{ __('P3 — Medium') }}</option>
                    <option value="4">{{ __('P4') }}</option>
                    <option value="5">{{ __('P5 — Critical') }}</option>
                </x-form.select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="datetime-local" name="formScheduledAt" label="{{ __('Scheduled at') }}"
                    wire:model="formScheduledAt" />
                <x-form.input type="datetime-local" name="formDueAt" label="{{ __('Due at') }}"
                    wire:model="formDueAt" />
            </div>

            <x-form.select name="formAssignedTo" label="{{ __('Assign to (optional)') }}" wire:model="formAssignedTo">
                <option value="">{{ __('Unassigned') }}</option>
                @foreach ($carerOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-form.select>

            <x-form.select name="formRecurringPattern" label="{{ __('Repeats (optional)') }}"
                wire:model="formRecurringPattern">
                <option value="">{{ __('Does not repeat') }}</option>
                <option value="daily">{{ __('Daily') }}</option>
                <option value="weekly">{{ __('Weekly') }}</option>
                <option value="every_n_days:3">{{ __('Every 3 days') }}</option>
            </x-form.select>

            <div class="flex gap-6">
                <x-form.checkbox name="formRequiresPhoto" label="{{ __('Requires photo') }}"
                    wire:model="formRequiresPhoto" />
                <x-form.checkbox name="formRequiresSignature" label="{{ __('Requires signature') }}"
                    wire:model="formRequiresSignature" />
            </div>
        </div>

        <x-slot:footer>
            <x-button wire:click="saveTask">{{ __('Save task') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
