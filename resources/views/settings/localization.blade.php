@extends('layouts.main')
@section('title', 'Localization Settings')
@section('content')
    <x-page-header title="{{ __('Settings') }}" subtitle="{{ __('Manage your workspace preferences') }}" icon="ik ik-settings"
                   :breadcrumbs="['Home' => url('dashboard'), 'Settings' => url('settings'), 'Localization' => null]" />

    <x-settings-layout active="localization">
        <form @submit.prevent class="space-y-6">
            <x-card>
                <x-slot:header>{{ __('Regional Settings') }}</x-slot:header>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form.select name="language" label="{{ __('Default language') }}">
                        <option selected>{{ __('English (US)') }}</option>
                        <option>{{ __('English (UK)') }}</option>
                        <option>{{ __('Spanish') }}</option>
                        <option>{{ __('French') }}</option>
                        <option>{{ __('German') }}</option>
                    </x-form.select>
                    <x-form.select name="timezone" label="{{ __('Timezone') }}">
                        <option selected>{{ __('(UTC-08:00) Pacific Time') }}</option>
                        <option>{{ __('(UTC-05:00) Eastern Time') }}</option>
                        <option>{{ __('(UTC+00:00) UTC') }}</option>
                        <option>{{ __('(UTC+01:00) Central European Time') }}</option>
                    </x-form.select>
                    <x-form.select name="date_format" label="{{ __('Date format') }}">
                        <option selected>{{ __('MM/DD/YYYY') }}</option>
                        <option>{{ __('DD/MM/YYYY') }}</option>
                        <option>{{ __('YYYY-MM-DD') }}</option>
                    </x-form.select>
                    <x-form.select name="currency" label="{{ __('Currency') }}">
                        <option selected>{{ __('USD — US Dollar') }}</option>
                        <option>{{ __('EUR — Euro') }}</option>
                        <option>{{ __('GBP — British Pound') }}</option>
                        <option>{{ __('JPY — Japanese Yen') }}</option>
                    </x-form.select>
                    <x-form.select name="number_format" label="{{ __('Number format') }}">
                        <option selected>{{ __('1,234.56') }}</option>
                        <option>{{ __('1.234,56') }}</option>
                        <option>{{ __('1 234.56') }}</option>
                    </x-form.select>
                </div>
                <div class="mt-5 divide-y divide-gray-100 border-t border-gray-100">
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Prices include tax') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Display product prices with tax already applied.') }}</p>
                        </div>
                        <x-form.toggle name="prices_include_tax" :checked="true" color="green" />
                    </div>
                </div>
            </x-card>

            <x-card no-padding>
                <x-slot:header>{{ __('Tax Rates') }}</x-slot:header>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">{{ __('Name') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Rate %') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Region') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ([['Standard','20%','United Kingdom'],['Reduced','10%','European Union'],['Sales Tax','8.5%','California, US'],['GST','5%','Canada']] as [$name,$rate,$region])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-700">{{ $name }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $rate }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $region }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-card>

            <x-save-bar :cancel="url('settings')" />
        </form>
    </x-settings-layout>
@endsection
