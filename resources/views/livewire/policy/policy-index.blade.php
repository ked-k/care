<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Policies') }}" subtitle="{{ __('Organisational policies and required reading') }}"
        icon="ik ik-book" :breadcrumbs="['Home' => url('dashboard'), 'Policies' => null]">
        <div class="flex items-center gap-3">
            <select wire:model.live="statusFilter"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <option value="active">{{ __('Active only') }}</option>
                <option value="all">{{ __('All') }}</option>
            </select>
            @if ($canManage)
                <x-button variant="primary" size="sm" wire:click="openCreateForm"
                    @click="$dispatch('open-drawer', 'policy-form')">
                    <i class="ik ik-plus mr-1"></i>{{ __('New policy') }}
                </x-button>
            @endif
        </div>
    </x-page-header>

    <x-card no-padding hover>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Title') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Category') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Version') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Review Date') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Acknowledged') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($policies as $policy)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="policy-{{ $policy->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">
                                {{ $policy->title }}
                                @if ($policy->is_mandatory_reading)
                                    <x-badge color="amber">{{ __('Mandatory') }}</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $policy->category)) }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">v{{ $policy->version }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $policy->review_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $policy->acknowledgments_count }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                @if ($policy->document)
                                    <a href="{{ $policy->document->url() }}" target="_blank"
                                        class="text-primary-600 hover:underline text-sm font-medium">{{ __('Document') }}</a>
                                @endif
                                @if ($policy->acknowledged_by_me)
                                    <x-badge color="success">{{ __('Acknowledged') }}</x-badge>
                                @else
                                    <button type="button" wire:click="acknowledge('{{ $policy->id }}')"
                                        class="text-primary-600 hover:underline text-sm font-medium">{{ __('Acknowledge') }}</button>
                                @endif
                                @if ($canManage)
                                    <button type="button" wire:click="openEditForm('{{ $policy->id }}')"
                                        @click="$dispatch('open-drawer', 'policy-form')"
                                        class="text-primary-600 hover:underline text-sm font-medium">{{ __('Edit') }}</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-empty-state title="{{ __('No policies published yet') }}" icon="ik ik-book" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-drawer name="policy-form" title="{{ $editingPolicyId ? __('Edit policy') : __('New policy') }}" width="w-[30rem]">
        <div class="space-y-4">
            <x-form.input name="formTitle" label="{{ __('Title') }}" wire:model="formTitle" required />
            <x-form.select name="formCategory" label="{{ __('Category') }}" wire:model="formCategory">
                <option value="safeguarding">{{ __('Safeguarding') }}</option>
                <option value="data_protection">{{ __('Data Protection') }}</option>
                <option value="medication">{{ __('Medication') }}</option>
                <option value="health_safety">{{ __('Health & Safety') }}</option>
                <option value="infection_control">{{ __('Infection Control') }}</option>
                <option value="hr">{{ __('HR') }}</option>
                <option value="other">{{ __('Other') }}</option>
            </x-form.select>
            <x-form.textarea name="formDescription" label="{{ __('Description (optional)') }}" rows="3" wire:model="formDescription" />

            <div class="grid grid-cols-2 gap-3">
                <x-form.input name="formVersion" label="{{ __('Version') }}" wire:model="formVersion" required />
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Document (optional)') }}</label>
                    <input type="file" wire:model="formDocument"
                        class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-primary-600">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="formEffectiveDate" label="{{ __('Effective date') }}" wire:model="formEffectiveDate" required />
                <x-form.input type="date" name="formReviewDate" label="{{ __('Review date') }}" wire:model="formReviewDate" required />
            </div>

            <div class="flex gap-6">
                <x-form.checkbox name="formIsMandatoryReading" label="{{ __('Mandatory reading') }}" wire:model="formIsMandatoryReading" />
                <x-form.checkbox name="formIsActive" label="{{ __('Active') }}" wire:model="formIsActive" />
            </div>
        </div>
        <x-slot:footer>
            <x-button wire:click="savePolicy">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
