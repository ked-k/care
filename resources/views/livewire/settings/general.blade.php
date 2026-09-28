@extends('layouts.main')
@section('title', 'Settings')
@section('content')
    <x-page-header title="{{ __('Settings') }}" subtitle="{{ __('Manage your workspace preferences') }}" icon="ik ik-settings"
                   :breadcrumbs="['Home' => url('dashboard'), 'Settings' => null]" />

    <x-settings-layout active="general">
        <form wire:submit.prevent="save" class="space-y-6">
            <x-card>
                <x-slot:header>{{ __('General') }}</x-slot:header>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form.input name="app_name" label="{{ __('Application name') }}" wire:model.defer="app_name" icon="ik ik-box" />
                    <x-form.input name="support_email" type="email" label="{{ __('Support email') }}" wire:model.defer="support_email" icon="ik ik-mail" />
                    <x-form.input name="contact_phone" label="{{ __('Contact phone') }}" wire:model.defer="contact_phone" icon="ik ik-phone" />
                </div>
            </x-card>

            <x-save-bar :cancel="url('settings')" />
        </form>
    </x-settings-layout>
@endsection
