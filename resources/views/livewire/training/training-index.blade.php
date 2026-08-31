<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Training') }}" subtitle="{{ __('Modules and your completion status') }}"
        icon="ik ik-award" :breadcrumbs="['Home' => url('dashboard'), 'Training' => null]">
        @if ($canManage)
            <x-button variant="primary" size="sm" wire:click="openCreateForm"
                @click="$dispatch('open-drawer', 'training-module-form')">
                <i class="ik ik-plus mr-1"></i>{{ __('New module') }}
            </x-button>
        @endif
    </x-page-header>

    <x-card no-padding hover>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Module') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Category') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Duration') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Your status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($modules as $module)
                        @php $mine = $module->progress->first(); @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="module-{{ $module->id }}">
                            <td class="px-5 py-3">
                                <div class="font-semibold text-gray-700 dark:text-gray-200">{{ $module->title }}</div>
                                @if ($module->description)
                                    <div class="text-xs text-gray-400">{{ $module->description }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ ucfirst($module->category) }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $module->duration_minutes ? $module->duration_minutes.' min' : '—' }}</td>
                            <td class="px-5 py-3">
                                @if ($mine?->status === 'completed')
                                    <x-badge color="success">{{ __('Completed') }}</x-badge>
                                @elseif ($mine)
                                    <x-badge color="primary">{{ __('Started') }}</x-badge>
                                @else
                                    <x-badge color="secondary">{{ __('Not started') }}</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                @if ($module->url)
                                    <a href="{{ $module->url }}" target="_blank"
                                        class="text-primary-600 hover:underline text-sm font-medium">{{ __('Open') }}</a>
                                @endif
                                <button type="button" wire:click="openLogForm('{{ $module->id }}')"
                                    @click="$dispatch('open-drawer', 'training-log-form')"
                                    class="text-primary-600 hover:underline text-sm font-medium">{{ __('Log progress') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10">
                                <x-empty-state title="{{ __('No training modules yet') }}" icon="ik ik-award" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-drawer name="training-module-form" title="{{ __('New training module') }}">
        <div class="space-y-4">
            <x-form.input name="formTitle" label="{{ __('Title') }}" wire:model="formTitle" required />
            <x-form.textarea name="formDescription" label="{{ __('Description (optional)') }}" rows="3" wire:model="formDescription" />
            <x-form.input name="formUrl" label="{{ __('Link (optional)') }}" wire:model="formUrl" placeholder="https://..." />
            <div class="grid grid-cols-2 gap-3">
                <x-form.select name="formCategory" label="{{ __('Category') }}" wire:model="formCategory">
                    <option value="safeguarding">{{ __('Safeguarding') }}</option>
                    <option value="data_protection">{{ __('Data Protection') }}</option>
                    <option value="medication">{{ __('Medication') }}</option>
                    <option value="health_safety">{{ __('Health & Safety') }}</option>
                    <option value="first_aid">{{ __('First Aid') }}</option>
                    <option value="infection_prevention">{{ __('Infection Prevention') }}</option>
                    <option value="mental_capacity">{{ __('Mental Capacity') }}</option>
                    <option value="moving_handling">{{ __('Moving & Handling') }}</option>
                    <option value="other">{{ __('Other') }}</option>
                </x-form.select>
                <x-form.input type="number" name="formDurationMinutes" label="{{ __('Duration (minutes)') }}" wire:model="formDurationMinutes" />
            </div>
        </div>
        <x-slot:footer>
            <x-button wire:click="saveModule">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-drawer name="training-log-form" title="{{ __('Log your progress') }}">
        <div class="space-y-4">
            <x-form.select name="logStatus" label="{{ __('Status') }}" wire:model="logStatus">
                <option value="started">{{ __('Started') }}</option>
                <option value="completed">{{ __('Completed') }}</option>
            </x-form.select>
            <x-form.input type="number" name="logScore" label="{{ __('Score (optional)') }}" wire:model="logScore" placeholder="0-100" />
        </div>
        <x-slot:footer>
            <x-button wire:click="logProgress">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
