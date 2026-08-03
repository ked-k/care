@extends('layouts.main')
@section('title', 'Settings')
@section('content')
    <x-page-header title="{{ __('Settings') }}" subtitle="{{ __('Manage your workspace preferences') }}" icon="ik ik-settings"
                   :breadcrumbs="['Home' => url('dashboard'), 'Settings' => null]" />

    <x-settings-layout active="general">
        <form @submit.prevent class="space-y-6">
            <x-card>
                <x-slot:header>{{ __('General') }}</x-slot:header>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form.input name="app_name" label="{{ __('Application name') }}" value="Radminly" icon="ik ik-box" />
                    <x-form.input name="support_email" type="email" label="{{ __('Support email') }}" value="support@radminly.app" icon="ik ik-mail" />
                    <x-form.input name="contact_phone" label="{{ __('Contact phone') }}" value="+1 219-122-1234" icon="ik ik-phone" />
                    <x-form.select name="default_landing" label="{{ __('Default landing page') }}">
                        <option>{{ __('Dashboard') }}</option>
                        <option>{{ __('POS') }}</option>
                        <option>{{ __('Inventory') }}</option>
                    </x-form.select>
                </div>
            </x-card>

            <x-card>
                <x-slot:header>{{ __('Preferences') }}</x-slot:header>
                <div class="divide-y divide-gray-100">
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Compact tables') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Reduce row height to fit more data on screen.') }}</p>
                        </div>
                        <x-form.toggle name="compact_tables" />
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Show weekly digest') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Email a summary of activity every Monday.') }}</p>
                        </div>
                        <x-form.toggle name="weekly_digest" :checked="true" color="green" />
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Maintenance mode') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Temporarily take the storefront offline.') }}</p>
                        </div>
                        <x-form.toggle name="maintenance" color="accent" />
                    </div>
                </div>
            </x-card>

            <x-save-bar :cancel="url('settings')" />
        </form>
    </x-settings-layout>
@endsection
