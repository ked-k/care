@extends('layouts.main')
@section('title', 'Company Settings')
@section('content')
    <x-page-header title="{{ __('Settings') }}" subtitle="{{ __('Manage your workspace preferences') }}" icon="ik ik-settings"
                   :breadcrumbs="['Home' => url('dashboard'), 'Settings' => url('settings'), 'Company' => null]" />

    <x-settings-layout active="company">
        <form wire:submit.prevent="save" class="space-y-6">
            <x-card>
                <x-slot:header>{{ __('Company Profile') }}</x-slot:header>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form.input wire:model.defer="legal_name" name="legal_name" label="{{ __('Legal name') }}" icon="ik ik-briefcase" />
                    <x-form.input wire:model.defer="trading_name" name="trading_name" label="{{ __('Trading name') }}" icon="ik ik-tag" />
                    <x-form.input wire:model.defer="phone" name="phone" label="{{ __('Phone') }}" icon="ik ik-phone" />
                </div>
            </x-card>

            <x-save-bar :cancel="url('settings')" />
        </form>
    </x-settings-layout>
@endsection
