@extends('layouts.main')
@section('title', 'Badges')
@section('content')
    <x-page-header title="{{ __('Badges') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-box"
        :breadcrumbs="['Home' => route('dashboard'), 'UI' => '#', 'Basic' => '#', 'Badges' => null]" />

    @php
        $badgeColors = ['primary', 'dark', 'success', 'danger', 'gray'];
    @endphp

    <div class="space-y-5">
        <x-card>
            <x-slot:header>{{ __('Colors') }}</x-slot:header>
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($badgeColors as $color)
                    <x-badge color="{{ $color }}">{{ __(ucfirst($color)) }}</x-badge>
                @endforeach
            </div>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Links') }}</x-slot:header>
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($badgeColors as $color)
                    <a href="#"><x-badge color="{{ $color }}">{{ __(ucfirst($color)) }}</x-badge></a>
                @endforeach
            </div>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Counter Badges') }}</x-slot:header>
            <x-button variant="primary">
                {{ __('Notifications') }}
                <x-badge color="dark">4</x-badge>
            </x-button>
        </x-card>
    </div>
@endsection
