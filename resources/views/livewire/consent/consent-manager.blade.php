<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Consent') }}" subtitle="{{ $serviceUser->name }}" icon="ik ik-check-circle"
        :breadcrumbs="['Home' => url('dashboard'), 'Service Users' => route('service-users.index'), $serviceUser->name => null]">
        @if ($canManage)
            <x-button variant="primary" size="sm" wire:click="openRecordForm"
                @click="$dispatch('open-drawer', 'consent-form')">
                <i class="ik ik-plus mr-1"></i>{{ __('Record consent') }}
            </x-button>
        @endif
    </x-page-header>

    <x-card no-padding hover>
        <x-slot:header>{{ __('Consent records') }}</x-slot:header>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Granted By') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Granted') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Expires') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        @if ($canManage)
                            <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($consents as $consent)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="consent-{{ $consent->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">{{ $consent->typeLabel() }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $consent->grantedBy->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $consent->granted_at?->format('d M Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $consent->expires_at?->format('d M Y') ?? __('No expiry') }}</td>
                            <td class="px-5 py-3"><x-badge color="{{ $consent->statusColor() }}">{{ $consent->statusLabel() }}</x-badge></td>
                            @if ($canManage)
                                <td class="px-5 py-3 text-right">
                                    @if ($consent->isActive())
                                        <button type="button" wire:click="openRevokeForm('{{ $consent->id }}')"
                                            @click="$dispatch('open-drawer', 'consent-revoke')"
                                            class="text-accent-500 hover:underline text-sm font-medium">{{ __('Revoke') }}</button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 6 : 5 }}" class="px-5 py-10">
                                <x-empty-state title="{{ __('No consent recorded yet') }}"
                                    description="{{ __('Record what this person (or their representative) has consented to.') }}"
                                    icon="ik ik-check-circle" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-drawer name="consent-form" title="{{ __('Record consent') }}">
        <div class="space-y-4">
            <x-form.select name="formConsentType" label="{{ __('Consent type') }}" wire:model="formConsentType" required>
                <option value="">{{ __('Select a type') }}</option>
                @foreach ($this->availableTypes() as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </x-form.select>

            <x-form.checkbox name="formGranted" label="{{ __('Consent was granted') }}" wire:model="formGranted" />
            <p class="text-xs text-gray-400">{{ __('Leave unchecked to record that consent was explicitly declined.') }}</p>

            <x-form.input type="date" name="formExpiresAt" label="{{ __('Expires (optional)') }}" wire:model="formExpiresAt" />
            <x-form.textarea name="formNotes" label="{{ __('Notes (optional)') }}" rows="3" wire:model="formNotes" />
        </div>
        <x-slot:footer>
            <x-button wire:click="recordConsent">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-drawer name="consent-revoke" title="{{ __('Revoke consent') }}">
        <x-form.textarea name="revokeNotes" label="{{ __('Reason (optional)') }}" rows="3" wire:model="revokeNotes" />
        <x-slot:footer>
            <x-button wire:click="revoke">{{ __('Revoke') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
