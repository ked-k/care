@extends('layouts.main')
@section('title', 'Security Settings')
@section('content')
    <x-page-header title="{{ __('Settings') }}" subtitle="{{ __('Manage your workspace preferences') }}" icon="ik ik-settings"
                   :breadcrumbs="['Home' => url('dashboard'), 'Settings' => url('settings'), 'Security' => null]" />

    <x-settings-layout active="security">
        <form @submit.prevent class="space-y-6">
            <x-card>
                <x-slot:header>{{ __('Change Password') }}</x-slot:header>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-form.input name="current_password" type="password" label="{{ __('Current password') }}" icon="ik ik-lock" />
                    </div>
                    <x-form.input name="new_password" type="password" label="{{ __('New password') }}" icon="ik ik-lock"
                                  hint="{{ __('At least 8 characters with a number and symbol.') }}" />
                    <x-form.input name="new_password_confirmation" type="password" label="{{ __('Confirm new password') }}" icon="ik ik-lock" />
                </div>
            </x-card>

            <x-card>
                <x-slot:header>{{ __('Two-Factor Authentication') }}</x-slot:header>
                <div class="divide-y divide-gray-100">
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Authenticator app') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Require a one-time code from your authenticator at sign-in.') }}</p>
                        </div>
                        <x-form.toggle name="two_factor" color="green" />
                    </div>
                </div>
            </x-card>

            <x-card no-padding>
                <x-slot:header>{{ __('Active Sessions') }}</x-slot:header>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">{{ __('Device') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Location') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Last active') }}</th>
                            <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ([['Chrome on macOS','San Francisco, US','Active now',true],['Safari on iPhone','San Francisco, US','2 hours ago',false],['Firefox on Windows','New York, US','Yesterday',false],['Edge on Windows','London, UK','3 days ago',false]] as [$device,$location,$active,$current])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-700">
                                    <span class="inline-flex items-center gap-2">
                                        {{ $device }}
                                        @if ($current)
                                            <x-badge color="success">{{ __('This device') }}</x-badge>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ $location }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $active }}</td>
                                <td class="px-5 py-3 text-right">
                                    <x-button variant="outline" size="sm" type="button" :disabled="$current">
                                        <i class="ik ik-log-out"></i> {{ __('Revoke') }}
                                    </x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-card>

            <x-save-bar :cancel="url('settings')" />
        </form>
    </x-settings-layout>
@endsection
