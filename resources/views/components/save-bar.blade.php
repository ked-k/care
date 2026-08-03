@props(['cancel' => null])

<div class="sticky bottom-0 z-10 -mx-px flex items-center justify-end gap-3 rounded-b-2xl border-t border-gray-100 bg-white/90 px-5 py-3.5 backdrop-blur">
    {{ $slot ?? '' }}
    <x-button variant="outline" :href="$cancel">{{ __('Cancel') }}</x-button>
    <x-button type="submit" @click="window.toast(@js(__('Settings saved')), 'success')"><i class="ik ik-check"></i> {{ __('Save changes') }}</x-button>
</div>
