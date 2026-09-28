@extends('layouts.main')
@section('title', 'Security Settings')
@section('content')
    <x-page-header title="{{ __('Settings') }}" subtitle="{{ __('Manage your workspace preferences') }}" icon="ik ik-settings"
                   :breadcrumbs="['Home' => url('dashboard'), 'Settings' => url('settings'), 'Security' => null]" />

    <x-settings-layout active="security">
        <form wire:submit.prevent="save" class="space-y-6">
            <x-card>
                <x-slot:header>{{ __('Change Password') }}</x-slot:header>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-form.input name="current_password" type="password" label="{{ __('Current password') }}" icon="ik ik-lock" />
                    </div>
                    <x-form.input name="new_password" type="password" label="{{ __('New password') }}" icon="ik ik-lock" />
                    <x-form.input name="new_password_confirmation" type="password" label="{{ __('Confirm new password') }}" icon="ik ik-lock" />
                </div>
            </x-card>

            <x-save-bar :cancel="url('settings')" />
        </form>
    </x-settings-layout>
@endsection
