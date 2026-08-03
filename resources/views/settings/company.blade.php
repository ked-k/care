@extends('layouts.main')
@section('title', 'Company Settings')
@section('content')
    <x-page-header title="{{ __('Settings') }}" subtitle="{{ __('Manage your workspace preferences') }}" icon="ik ik-settings"
                   :breadcrumbs="['Home' => url('dashboard'), 'Settings' => url('settings'), 'Company' => null]" />

    <x-settings-layout active="company">
        <form @submit.prevent class="space-y-6">
            <x-card>
                <x-slot:header>{{ __('Company Profile') }}</x-slot:header>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form.input name="legal_name" label="{{ __('Legal name') }}" value="Radminly Holdings LLC" icon="ik ik-briefcase" />
                    <x-form.input name="trading_name" label="{{ __('Trading name') }}" value="Radminly" icon="ik ik-tag" />
                    <x-form.input name="registration_no" label="{{ __('Registration no.') }}" value="REG-2019-44821" icon="ik ik-hash" />
                    <x-form.input name="tax_id" label="{{ __('Tax ID') }}" value="TX-99-1284477" icon="ik ik-percent" />
                </div>
                <div class="mt-5">
                    <x-form.textarea name="address" label="{{ __('Address') }}" rows="3"
                                     value="1234 Market Street, Suite 500, San Francisco, CA 94103" />
                </div>
                <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form.input name="phone" label="{{ __('Phone') }}" value="+1 219-122-1234" icon="ik ik-phone" />
                    <x-form.input name="website" label="{{ __('Website') }}" value="https://radminly.app" icon="ik ik-globe" />
                </div>
            </x-card>

            <x-card>
                <x-slot:header>{{ __('Logo') }}</x-slot:header>
                <div class="flex items-center gap-5">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-300">
                        <i class="ik ik-image text-2xl"></i>
                    </div>
                    <div>
                        <x-button variant="outline" type="button"><i class="ik ik-upload"></i> {{ __('Upload logo') }}</x-button>
                        <p class="mt-2 text-xs text-gray-400">{{ __('PNG or SVG, up to 2 MB. Recommended 256×256.') }}</p>
                    </div>
                </div>
            </x-card>

            <x-save-bar :cancel="url('settings')" />
        </form>
    </x-settings-layout>
@endsection
