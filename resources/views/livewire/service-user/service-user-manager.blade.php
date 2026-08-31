<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Service Users') }}" subtitle="{{ __('The people your carers support') }}"
        icon="ik ik-users" :breadcrumbs="['Home' => url('dashboard'), 'Service Users' => null]">
        <div class="flex items-center gap-3">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search by name...') }}"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <label class="flex items-center gap-2 text-sm text-gray-500">
                <input type="checkbox" wire:model.live="showInactive"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                {{ __('Show inactive') }}
            </label>
            <x-button variant="primary" size="sm" wire:click="openCreateForm"
                @click="$dispatch('open-drawer', 'service-user-form')">
                <i class="ik ik-plus mr-1"></i>{{ __('Add service user') }}
            </x-button>
        </div>
    </x-page-header>

    <x-card no-padding hover>
        <x-table :paginator="$serviceUsers" title="{{ __('Service Users') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Date of Birth') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Next of Kin') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Consent') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($serviceUsers as $su)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="su-{{ $su->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">{{ $su->name }}
                            </td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                {{ $su->dob?->format('d M Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $su->next_of_kin_name ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ $su->consent_status ? 'success' : 'amber' }}">
                                    {{ $su->consent_status ? __('Recorded') : __('Pending') }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ $su->trashed() ? 'secondary' : 'success' }}">
                                    {{ $su->trashed() ? __('Inactive') : __('Active') }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-right space-x-2 whitespace-nowrap">
                                @unless ($su->trashed())
                                    <a href="{{ route('medications.manage', $su->id) }}" wire:navigate
                                        class="text-primary-600 hover:underline text-sm font-medium">{{ __('Medications') }}</a>
                                    <a href="{{ route('consents.manage', $su->id) }}" wire:navigate
                                        class="text-primary-600 hover:underline text-sm font-medium">{{ __('Consents') }}</a>
                                    <a href="{{ route('family.manage', $su->id) }}" wire:navigate
                                        class="text-primary-600 hover:underline text-sm font-medium">{{ __('Family') }}</a>
                                    <button type="button" wire:click="openEditForm('{{ $su->id }}')"
                                        @click="$dispatch('open-drawer', 'service-user-form')"
                                        class="text-primary-600 hover:underline text-sm font-medium">{{ __('Edit') }}</button>
                                @endunless
                                <button type="button" wire:click="toggleActive('{{ $su->id }}')"
                                    wire:confirm="{{ $su->trashed() ? __('Reactivate this service user?') : __('Mark this service user inactive?') }}"
                                    class="text-accent-500 hover:underline text-sm font-medium">
                                    {{ $su->trashed() ? __('Reactivate') : __('Deactivate') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-empty-state title="{{ __('No service users yet') }}"
                                    description="{{ __('Add the people your carers will support.') }}"
                                    icon="ik ik-users" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>

    <x-drawer name="service-user-form"
        title="{{ $editingServiceUserId ? __('Edit service user') : __('Add service user') }}" width="w-[30rem]">
        <div class="space-y-4">
            <x-form.input name="formName" label="{{ __('Name') }}" wire:model="formName" required />

            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="formDob" label="{{ __('Date of birth') }}" wire:model="formDob" />
                <x-form.select name="formGender" label="{{ __('Gender') }}" wire:model="formGender">
                    <option value="">{{ __('Not specified') }}</option>
                    <option value="female">{{ __('Female') }}</option>
                    <option value="male">{{ __('Male') }}</option>
                    <option value="other">{{ __('Other') }}</option>
                </x-form.select>
            </div>

            <x-form.textarea name="formAddress" label="{{ __('Address') }}" rows="2" wire:model="formAddress" />
            <x-form.input name="formNhsNumber" label="{{ __('NHS number (optional)') }}" wire:model="formNhsNumber" />

            <div class="grid grid-cols-2 gap-3">
                <x-form.input name="formNextOfKinName" label="{{ __('Next of kin name') }}"
                    wire:model="formNextOfKinName" />
                <x-form.input name="formNextOfKinContact" label="{{ __('Next of kin contact') }}"
                    wire:model="formNextOfKinContact" />
            </div>

            <x-form.checkbox name="formConsentStatus" label="{{ __('Consent recorded') }}"
                wire:model="formConsentStatus" />
        </div>

        <x-slot:footer>
            <x-button wire:click="saveServiceUser">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
