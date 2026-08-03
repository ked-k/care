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
</div>
