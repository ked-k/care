@extends('layouts.main')
@section('title', 'Buttons')
@section('content')
    <x-page-header title="{{ __('Buttons') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-box"
        :breadcrumbs="['Home' => route('dashboard'), 'UI' => '#', 'Basic' => '#', 'Buttons' => null]" />

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <x-card>
            <x-slot:header>{{ __('Normal buttons') }}</x-slot:header>
            <div class="flex flex-wrap items-center gap-2">
                <x-button variant="primary">{{ __('Primary') }}</x-button>
                <x-button variant="secondary">{{ __('Secondary') }}</x-button>
                <x-button variant="success">{{ __('Success') }}</x-button>
                <x-button variant="danger">{{ __('Danger') }}</x-button>
                <x-button variant="warning">{{ __('Warning') }}</x-button>
                <x-button variant="ghost">{{ __('Link') }}</x-button>
            </div>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Icon Buttons') }}</x-slot:header>
            <div class="flex flex-wrap items-center gap-2">
                <x-button variant="secondary"><i class="ik ik-settings"></i></x-button>
                <x-button variant="success"><i class="ik ik-mail"></i></x-button>
                <x-button variant="primary"><i class="ik ik-star"></i></x-button>
                <x-button variant="warning"><i class="ik ik-map-pin"></i></x-button>
                <x-button variant="danger"><i class="ik ik-refresh-cw"></i></x-button>
            </div>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Outlined buttons') }}</x-slot:header>
            <div class="flex flex-wrap items-center gap-2">
                <x-button variant="outline">{{ __('Default') }}</x-button>
                <x-button variant="outline">{{ __('Secondary') }}</x-button>
                <x-button variant="outline">{{ __('Success') }}</x-button>
                <x-button variant="outline">{{ __('Danger') }}</x-button>
                <x-button variant="outline">{{ __('Warning') }}</x-button>
            </div>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Button Sizes') }}</x-slot:header>
            <div class="flex flex-wrap items-center gap-2">
                <x-button variant="primary" size="sm">{{ __('Small') }}</x-button>
                <x-button variant="primary" size="md">{{ __('Medium') }}</x-button>
                <x-button variant="primary" size="lg">{{ __('Large') }}</x-button>
            </div>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Button Block') }}</x-slot:header>
            <x-button variant="primary" class="w-full">{{ __('Block Button') }}</x-button>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Buttons with Icons') }}</x-slot:header>
            <div class="flex flex-wrap items-center gap-2">
                <x-button variant="secondary"><i class="ik ik-heart"></i>{{ __('Default') }}</x-button>
                <x-button variant="primary"><i class="ik ik-star"></i>{{ __('Primary') }}</x-button>
                <x-button variant="success"><i class="ik ik-check-circle"></i>{{ __('Success') }}</x-button>
                <x-button variant="danger"><i class="ik ik-info"></i>{{ __('Warning') }}</x-button>
                <x-button variant="ghost"><i class="ik ik-share"></i>{{ __('Upload') }}</x-button>
            </div>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Grouped buttons') }}</x-slot:header>
            <div class="flex flex-wrap items-center gap-4">
                <div class="inline-flex overflow-hidden rounded-lg border border-gray-200">
                    <button type="button" class="border-r border-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">1</button>
                    <button type="button" class="border-r border-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">2</button>
                    <button type="button" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">3</button>
                </div>
                <div class="inline-flex overflow-hidden rounded-lg">
                    <button type="button" class="border-r border-primary-600 bg-primary-500 px-4 py-2 text-sm text-white hover:bg-primary-600">1</button>
                    <button type="button" class="border-r border-primary-600 bg-primary-500 px-4 py-2 text-sm text-white hover:bg-primary-600">2</button>
                    <button type="button" class="bg-primary-500 px-4 py-2 text-sm text-white hover:bg-primary-600">3</button>
                </div>
            </div>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Social Buttons') }}</x-slot:header>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#3b5998] text-white transition hover:opacity-90"><i class="fab fa-facebook-f"></i></button>
                <button type="button" class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#1da1f2] text-white transition hover:opacity-90"><i class="fab fa-twitter"></i></button>
                <button type="button" class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#ea4c89] text-white transition hover:opacity-90"><i class="fab fa-dribbble"></i></button>
                <button type="button" class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#0077b5] text-white transition hover:opacity-90"><i class="fab fa-linkedin"></i></button>
                <button type="button" class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#db4437] text-white transition hover:opacity-90"><i class="fab fa-google"></i></button>
            </div>
        </x-card>
    </div>
@endsection
