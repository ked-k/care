@extends('layouts.main')
@section('title', 'Appearance Settings')
@section('content')
    <x-page-header title="{{ __('Settings') }}" subtitle="{{ __('Manage your workspace preferences') }}" icon="ik ik-settings"
                   :breadcrumbs="['Home' => url('dashboard'), 'Settings' => url('settings'), 'Appearance' => null]" />

    <x-settings-layout active="appearance">
        <form @submit.prevent class="space-y-6">
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
                        <x-form.toggle name="dark_mode" />
                    </div>
                </div>
            </x-card>

            <x-card>
                <x-slot:header>{{ __('Accent Color') }}</x-slot:header>
                <p class="mb-3 text-sm text-gray-500">{{ __('Pick the accent used for highlights and active states.') }}</p>
                <div class="flex flex-wrap items-center gap-3">
                    @foreach ([['primary','bg-primary-500','ring-primary-300'],['green','bg-green-500','ring-green-300'],['amber','bg-amber-500','ring-amber-300'],['purple','bg-purple-500','ring-purple-300'],['cyan','bg-cyan-500','ring-cyan-300'],['accent','bg-accent-500','ring-accent-300']] as [$name,$bg,$ring])
                        <button type="button" title="{{ ucfirst($name) }}"
                                class="h-9 w-9 rounded-full {{ $bg }} ring-2 ring-offset-2 {{ $ring }} transition hover:scale-110 focus:outline-none"></button>
                    @endforeach
                </div>
            </x-card>

            <x-save-bar :cancel="url('settings')" />
        </form>
    </x-settings-layout>
@endsection
