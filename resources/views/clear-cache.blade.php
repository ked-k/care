@extends('layouts.main')
@section('title', 'Clear Cache')
@section('content')
    <x-page-header title="{{ __('Clear Cache') }}" subtitle="{{ __('Create new user, assign roles & permissions') }}" icon="ik ik-battery-charging"
        :breadcrumbs="['Home' => url('dashboard'), 'Clear Cache' => null]" />

    @include('include.message')

    <x-card>
        <div class="py-10 text-center">
            <i class="ik ik-battery-charging text-7xl text-green-500"></i>
            <h4 class="mt-4 text-xl font-semibold text-gray-800">{{ __('WOW! Cache Cleared!!') }}</h4>
        </div>
    </x-card>
@endsection
