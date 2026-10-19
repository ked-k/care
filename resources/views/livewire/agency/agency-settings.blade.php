<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Agency Settings') }}" subtitle="{{ __('Your organization\'s core details') }}"
                    icon="ik ik-settings" :breadcrumbs="['Home' => url('dashboard'), 'Agency Settings' => null]" />

    <x-card class="max-w-2xl" hover>
        <div class="space-y-4">
            <x-form.input name="formName" label="{{ __('Agency name') }}" wire:model="formName" required />
            <x-form.textarea name="formAddress" label="{{ __('Address') }}" rows="2" wire:model="formAddress" />

            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="email" name="formContactEmail" label="{{ __('Contact email') }}" wire:model="formContactEmail" />
                <x-form.input name="formPhone" label="{{ __('Phone') }}" wire:model="formPhone" />
            </div>

            <x-form.select name="formWeekStartsOn" label="{{ __('Week starts on') }}" wire:model="formWeekStartsOn">
                <option value="monday">{{ __('Monday') }}</option>
                <option value="sunday">{{ __('Sunday') }}</option>
            </x-form.select>
            <p class="text-xs text-gray-400 -mt-2">
                {{ __('For reference only right now — the Rota, Timesheet, and MAR Chart modules currently always build their weekly grids Monday to Sunday regardless of this setting.') }}
            </p>
        </div>

        <div class="mt-6 flex justify-end">
            <x-button variant="primary" wire:click="save">{{ __('Save settings') }}</x-button>
        </div>
    </x-card>

    {{-- Batch 35: geofenced check-in restriction. See
         App\Livewire\Rota\ShiftVisitComponent::checkIn() for the enforcement
         itself and App\Models\ServiceUser for where a client's location
         comes from (auto-geocoded address, or a manual pin). --}}
    <x-card class="max-w-2xl mt-6" hover>
        <x-slot:header>{{ __('Carer check-in') }}</x-slot:header>

        <div class="space-y-4">
            <x-form.checkbox name="formAllowRemoteCheckin"
                label="{{ __('Allow remote check-in') }}"
                wire:model="formAllowRemoteCheckin" />
            <p class="text-xs text-gray-400 -mt-2">
                {{ __("When off, a carer who isn't physically at the service user's address can't check in at all. When on, they still can — it's recorded as a remote/override check-in, with the distance logged, and Admin and Manager are notified.") }}
            </p>

            <x-form.input type="number" min="20" max="2000" name="formCheckinRadiusMeters"
                label="{{ __('Check-in radius (meters)') }}" wire:model="formCheckinRadiusMeters" />
            <p class="text-xs text-gray-400 -mt-2">
                {{ __('How close a carer\'s device must be to a service user\'s saved location to count as a verified check-in. 150m is a reasonable default for normal GPS drift.') }}
            </p>
        </div>

        <div class="mt-6 flex justify-end">
            <x-button variant="primary" wire:click="save">{{ __('Save settings') }}</x-button>
        </div>
    </x-card>
</div>
