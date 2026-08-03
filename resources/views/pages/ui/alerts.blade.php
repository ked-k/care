@extends('layouts.main')
@section('title', 'Alerts')
@section('content')
    <x-page-header title="{{ __('Alerts') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-box"
        :breadcrumbs="['Home' => route('dashboard'), 'UI' => '#', 'Basic' => '#', 'Alerts' => null]" />

    @php
        $alertColors = [
            'primary'   => 'border-primary-200 bg-primary-50 text-primary-700',
            'secondary' => 'border-gray-200 bg-gray-50 text-gray-700',
            'success'   => 'border-green-200 bg-green-50 text-green-700',
            'danger'    => 'border-accent-500/30 bg-accent-500/10 text-accent-600',
            'warning'   => 'border-amber-200 bg-amber-50 text-amber-700',
            'info'      => 'border-primary-200 bg-primary-50 text-primary-600',
            'light'     => 'border-gray-100 bg-white text-gray-600',
            'dark'      => 'border-gray-700 bg-gray-700/10 text-gray-700',
        ];
        $alertFill = [
            'primary'   => 'bg-primary-500 text-white',
            'secondary' => 'bg-gray-500 text-white',
            'success'   => 'bg-green-500 text-white',
            'danger'    => 'bg-accent-500 text-white',
            'warning'   => 'bg-amber-500 text-white',
            'info'      => 'bg-primary-400 text-white',
            'light'     => 'bg-gray-100 text-gray-700',
            'dark'      => 'bg-slate-700 text-white',
        ];
    @endphp

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <x-card>
            <x-slot:header>{{ __('Soft Alerts') }}</x-slot:header>
            <div class="space-y-3">
                @foreach ($alertColors as $key => $cls)
                    <div role="alert" class="rounded-lg border px-4 py-3 text-sm {{ $cls }}">
                        {{ __('A simple :type alert—check it out!', ['type' => $key]) }}
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Filled Alerts') }}</x-slot:header>
            <div class="space-y-3">
                @foreach ($alertFill as $key => $cls)
                    <div role="alert" class="rounded-lg px-4 py-3 text-sm {{ $cls }}">
                        {{ __('A simple :type alert—check it out!', ['type' => $key]) }}
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    <div class="mt-5">
        <x-card>
            <x-slot:header>{{ __('Dismissing Alert') }}</x-slot:header>
            <div x-data="{ show: true }" x-show="show" x-transition role="alert"
                 class="flex items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                <span><strong>Holy guacamole!</strong> You should check in on some of those fields below.</span>
                <button type="button" @click="show = false" class="opacity-60 hover:opacity-100"><i class="ik ik-x"></i></button>
            </div>
        </x-card>
    </div>
@endsection
