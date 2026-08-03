@extends('layouts.main')
@section('title', 'Notification Settings')
@section('content')
    <x-page-header title="{{ __('Settings') }}" subtitle="{{ __('Manage your workspace preferences') }}" icon="ik ik-settings"
                   :breadcrumbs="['Home' => url('dashboard'), 'Settings' => url('settings'), 'Notifications' => null]" />

    <x-settings-layout active="notifications">
        <form @submit.prevent class="space-y-6">
            <x-card>
                <x-slot:header>{{ __('Email') }}</x-slot:header>
                <div class="divide-y divide-gray-100">
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('New order') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Email me when a new order is placed.') }}</p>
                        </div>
                        <x-form.toggle name="email_new_order" :checked="true" color="green" />
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Low stock') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Alert me when a product drops below its reorder level.') }}</p>
                        </div>
                        <x-form.toggle name="email_low_stock" :checked="true" color="green" />
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Payment received') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Email me when a customer payment clears.') }}</p>
                        </div>
                        <x-form.toggle name="email_payment" color="green" />
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Weekly report') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Send a performance summary every Monday morning.') }}</p>
                        </div>
                        <x-form.toggle name="email_weekly_report" :checked="true" color="green" />
                    </div>
                </div>
            </x-card>

            <x-card>
                <x-slot:header>{{ __('Push') }}</x-slot:header>
                <div class="divide-y divide-gray-100">
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('New order') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Push a notification to your devices for each order.') }}</p>
                        </div>
                        <x-form.toggle name="push_new_order" :checked="true" />
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Low stock') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Notify me on-device about low stock items.') }}</p>
                        </div>
                        <x-form.toggle name="push_low_stock" />
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Payment received') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Push an alert when a payment is confirmed.') }}</p>
                        </div>
                        <x-form.toggle name="push_payment" :checked="true" />
                    </div>
                </div>
            </x-card>

            <x-card>
                <x-slot:header>{{ __('System') }}</x-slot:header>
                <div class="divide-y divide-gray-100">
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Low stock') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Show in-app banners for low stock warnings.') }}</p>
                        </div>
                        <x-form.toggle name="system_low_stock" :checked="true" color="amber" />
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Weekly report') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Surface the weekly digest in your notification center.') }}</p>
                        </div>
                        <x-form.toggle name="system_weekly_report" color="amber" />
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Maintenance alerts') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Warn me before scheduled maintenance windows.') }}</p>
                        </div>
                        <x-form.toggle name="system_maintenance" :checked="true" color="accent" />
                    </div>
                </div>
            </x-card>

            <x-save-bar :cancel="url('settings')" />
        </form>
    </x-settings-layout>
@endsection
