@extends('layouts.main')
@section('title', 'Localization Settings')
@section('content')
    <x-page-header title="{{ __('Settings') }}" subtitle="{{ __('Manage your workspace preferences') }}" icon="ik ik-settings"
                   :breadcrumbs="['Home' => url('dashboard'), 'Settings' => url('settings'), 'Localization' => null]" />

    <x-settings-layout active="localization">
        <form wire:submit.prevent="save" class="space-y-6">
            <x-card>
                <x-slot:header>{{ __('Regional Settings') }}</x-slot:header>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form.select wire:model.defer="language" name="language" label="{{ __('Default language') }}">
                        <option value="en_US">{{ __('English (US)') }}</option>
                        <option value="en_GB">{{ __('English (UK)') }}</option>
                        <option value="es">{{ __('Spanish') }}</option>
                    </x-form.select>
                    <x-form.select wire:model.defer="timezone" name="timezone" label="{{ __('Timezone') }}">
                        <option value="UTC">{{ __('(UTC+00:00) UTC') }}</option>
                        <option value="Europe/London">{{ __('(UTC+01:00) Central European Time') }}</option>
                    </x-form.select>
                </div>
            </x-card>

            <x-save-bar :cancel="url('settings')" />
        </form>
    </x-settings-layout>
@endsection
