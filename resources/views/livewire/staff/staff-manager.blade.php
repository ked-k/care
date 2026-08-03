<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Staff') }}" subtitle="{{ __('Carers and managers, with their pay profiles') }}"
        icon="ik ik-user" :breadcrumbs="['Home' => url('dashboard'), 'Staff' => null]">
        <div class="flex items-center gap-3">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search by name...') }}"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <x-button variant="primary" size="sm" @click="$dispatch('open-drawer', 'staff-form')"
                wire:click="openCreateForm">
                <i class="ik ik-plus mr-1"></i>{{ __('Add staff member') }}
            </x-button>
        </div>
    </x-page-header>

    <x-card no-padding hover>
        <x-table :paginator="$staff" title="{{ __('Staff') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Role') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Employee No.') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Hourly Rate') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($staff as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="user-{{ $user->id }}">
                            <td class="px-5 py-3">
                                <div class="font-semibold text-gray-700 dark:text-gray-200">{{ $user->name }}</div>
                                <div class="text-xs text-gray-400">{{ $user->email }}</div>
                            </td>
                            <td class="px-5 py-3">
                                <x-badge
                                    color="{{ match ($user->roles->first()?->name) {
                                        'admin' => 'danger',
                                        'manager' => 'primary',
                                        default => 'secondary',
                                    } }}">
                                    {{ ucfirst($user->roles->first()?->name ?? __('No role')) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                {{ $user->payProfile?->employee_no ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                {{ number_format($user->payProfile?->hourly_rate ?? 0, 2) }}</td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ $user->is_active ? 'success' : 'secondary' }}">
                                    {{ $user->is_active ? __('Active') : __('Inactive') }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-right space-x-2 whitespace-nowrap">
                                <button type="button" wire:click="openEditForm({{ $user->id }})"
                                    @click="$dispatch('open-drawer', 'staff-form')"
                                    class="text-primary-600 hover:underline text-sm font-medium">{{ __('Edit') }}</button>
                                <button type="button" wire:click="toggleActive({{ $user->id }})"
                                    wire:confirm="{{ $user->is_active ? __('Deactivate this staff member?') : __('Reactivate this staff member?') }}"
                                    class="text-accent-500 hover:underline text-sm font-medium">
                                    {{ $user->is_active ? __('Deactivate') : __('Reactivate') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-empty-state title="{{ __('No staff yet') }}"
                                    description="{{ __('Add a manager or carer to get started.') }}"
                                    icon="ik ik-user" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>

    <x-drawer name="staff-form" title="{{ $editingUserId ? __('Edit staff member') : __('Add staff member') }}"
        width="w-[32rem]">
        <div class="space-y-4">
            <h6 class="text-xs uppercase tracking-wide text-gray-400">{{ __('Account') }}</h6>
            <x-form.input name="formName" label="{{ __('Name') }}" wire:model="formName" required />
            <x-form.input type="email" name="formEmail" label="{{ __('Email') }}" wire:model="formEmail"
                required />
            <x-form.input type="password" name="formPassword"
                label="{{ $editingUserId ? __('New password (leave blank to keep current)') : __('Password') }}"
                wire:model="formPassword" :required="!$editingUserId" />

            <div class="grid grid-cols-2 gap-3">
                <x-form.select name="formRole" label="{{ __('Role') }}" wire:model="formRole" required>
                    @foreach ($roleOptions as $role)
                        <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                    @endforeach
                </x-form.select>
                <x-form.checkbox name="formIsActive" label="{{ __('Active') }}" wire:model="formIsActive" />
            </div>

            <h6 class="text-xs uppercase tracking-wide text-gray-400 pt-2">{{ __('Pay Profile') }}</h6>
            <div class="grid grid-cols-2 gap-3">
                <x-form.input name="formEmployeeNo" label="{{ __('Employee No.') }}" wire:model="formEmployeeNo"
                    required />
                <x-form.input name="formJobTitle" label="{{ __('Job title') }}" wire:model="formJobTitle" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-form.select name="formEmploymentType" label="{{ __('Employment type') }}"
                    wire:model="formEmploymentType">
                    <option value="full_time">{{ __('Full time') }}</option>
                    <option value="part_time">{{ __('Part time') }}</option>
                    <option value="casual">{{ __('Casual') }}</option>
                    <option value="bank">{{ __('Bank') }}</option>
                </x-form.select>
                <x-form.select name="formManagerId" label="{{ __('Manager (optional)') }}" wire:model="formManagerId">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($managerOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-form.select>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <x-form.input type="number" step="0.01" min="0" name="formHourlyRate"
                    label="{{ __('Hourly rate') }}" wire:model="formHourlyRate" required />
                <x-form.input type="number" step="0.1" min="1" name="formOvertimeMultiplier"
                    label="{{ __('OT multiplier') }}" wire:model="formOvertimeMultiplier" required />
                <x-form.input type="number" step="0.5" min="1" name="formWeeklyOvertimeThresholdHours"
                    label="{{ __('OT threshold (hrs)') }}" wire:model="formWeeklyOvertimeThresholdHours" required />
            </div>

            <x-form.select name="formPayFrequency" label="{{ __('Pay frequency') }}" wire:model="formPayFrequency">
                <option value="weekly">{{ __('Weekly') }}</option>
                <option value="biweekly">{{ __('Biweekly') }}</option>
                <option value="monthly">{{ __('Monthly') }}</option>
            </x-form.select>

            <div class="grid grid-cols-2 gap-3">
                <x-form.input name="formBankName" label="{{ __('Bank name (optional)') }}"
                    wire:model="formBankName" />
                <x-form.input name="formBankAccountNo" label="{{ __('Bank account (optional)') }}"
                    wire:model="formBankAccountNo" />
            </div>
            <x-form.input name="formMobileMoneyNumber" label="{{ __('Mobile money number (optional)') }}"
                wire:model="formMobileMoneyNumber" />
        </div>

        <x-slot:footer>
            <x-button wire:click="saveStaff">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
