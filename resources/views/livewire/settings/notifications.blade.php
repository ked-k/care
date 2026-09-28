@extends('layouts.main')
@section('title', 'Notification Settings')
@section('content')
    <x-page-header title="{{ __('Settings') }}" subtitle="{{ __('Manage your workspace preferences') }}" icon="ik ik-settings"
                   :breadcrumbs="['Home' => url('dashboard'), 'Settings' => url('settings'), 'Notifications' => null]" />

    <x-settings-layout active="notifications">
        <form wire:submit.prevent="save" class="space-y-6">
            <x-card>
                <x-slot:header>{{ __('Email') }}</x-slot:header>
                <div class="divide-y divide-gray-100">
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('New order') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Email me when a new order is placed.') }}</p>
                        </div>
                        <x-form.toggle wire:model.defer="email_new_order" name="email_new_order" />
                    </div>
                </div>
            </x-card>

            <x-save-bar :cancel="url('settings')" />
        </form>
    </x-settings-layout>
@endsection
