@extends('layouts.main')
@section('title', 'Appearance Settings')
@section('content')
    <x-page-header title="{{ __('Settings') }}" subtitle="{{ __('Manage your workspace preferences') }}" icon="ik ik-settings"
                   :breadcrumbs="['Home' => url('dashboard'), 'Settings' => url('settings'), 'Appearance' => null]" />

    <x-settings-layout active="appearance">
        <form wire:submit.prevent="save" class="space-y-6">
            <x-card>
                <x-slot:header>{{ __('Theme') }}</x-slot:header>
                <p class="text-sm text-gray-500">{{ __('Fine-tune brand colors, radius and density in the live customizer.') }}</p>
                <div class="mt-4">
                    <x-button @click="$dispatch('open-theme')"><i class="ik ik-droplet"></i> {{ __('Open Theme Customizer') }}</x-button>
                </div>
            </x-card>

            <x-card>
                <x-slot:header>{{ __('Display') }}</x-slot:header>
                <div class="divide-y divide-gray-100">
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Dark mode') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Use a darker color scheme across the workspace.') }}</p>
                        </div>
                        <x-form.toggle wire:model.defer="dark_mode" name="dark_mode" />
                    </div>
                </div>
            </x-card>

            <x-save-bar :cancel="url('settings')" />
        </form>
    </x-settings-layout>
@endsection
