@extends('layouts.main')
@section('title', 'Navigation')
@section('content')
    <x-page-header title="{{ __('Navigation') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-box"
        :breadcrumbs="['Home' => route('dashboard'), 'UI' => '#', 'Basic' => '#', 'Navigation' => null]" />

    @php
        $navLink = 'rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-primary-600';
        $navActive = 'rounded-lg bg-primary-50 px-4 py-2 text-sm font-medium text-primary-600';
        $navDisabled = 'rounded-lg px-4 py-2 text-sm font-medium text-gray-300 cursor-not-allowed';
    @endphp

    <div class="space-y-5">
        <x-card>
            <x-slot:header>{{ __('Nav Basic') }}</x-slot:header>
            <ul class="flex flex-wrap items-center gap-1">
                <li><a class="{{ $navActive }}" href="#">{{ __('Active') }}</a></li>
                <li><a class="{{ $navLink }}" href="#">{{ __('Link') }}</a></li>
                <li><a class="{{ $navLink }}" href="#">{{ __('Link') }}</a></li>
                <li><a class="{{ $navDisabled }}" href="#">{{ __('Disabled') }}</a></li>
            </ul>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Nav Horizontal Alignment') }}</x-slot:header>
            <ul class="flex flex-wrap items-center justify-center gap-1">
                <li><a class="{{ $navActive }}" href="#">{{ __('Active') }}</a></li>
                <li><a class="{{ $navLink }}" href="#">{{ __('Link') }}</a></li>
                <li><a class="{{ $navLink }}" href="#">{{ __('Link') }}</a></li>
                <li><a class="{{ $navDisabled }}" href="#">{{ __('Disabled') }}</a></li>
            </ul>
            <ul class="mt-2 flex flex-wrap items-center justify-end gap-1">
                <li><a class="{{ $navActive }}" href="#">{{ __('Active') }}</a></li>
                <li><a class="{{ $navLink }}" href="#">{{ __('Link') }}</a></li>
                <li><a class="{{ $navLink }}" href="#">{{ __('Link') }}</a></li>
                <li><a class="{{ $navDisabled }}" href="#">{{ __('Disabled') }}</a></li>
            </ul>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Nav Vertical Alignment') }}</x-slot:header>
            <ul class="flex flex-col gap-1">
                <li><a class="inline-block {{ $navActive }}" href="#">{{ __('Active') }}</a></li>
                <li><a class="inline-block {{ $navLink }}" href="#">{{ __('Link') }}</a></li>
                <li><a class="inline-block {{ $navLink }}" href="#">{{ __('Link') }}</a></li>
                <li><a class="inline-block {{ $navDisabled }}" href="#">{{ __('Disabled') }}</a></li>
            </ul>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Nav Pills') }}</x-slot:header>
            <ul class="flex flex-wrap items-center gap-2">
                <li><a class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white" href="#">{{ __('Active') }}</a></li>
                <li><a class="{{ $navLink }}" href="#">{{ __('Link') }}</a></li>
                <li><a class="{{ $navLink }}" href="#">{{ __('Link') }}</a></li>
                <li><a class="{{ $navDisabled }}" href="#">{{ __('Disabled') }}</a></li>
            </ul>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Nav Pill and Justify') }}</x-slot:header>
            <ul class="grid grid-cols-4 gap-2 text-center">
                <li><a class="block rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white" href="#">{{ __('Active') }}</a></li>
                <li><a class="block {{ $navLink }}" href="#">{{ __('Longer nav link') }}</a></li>
                <li><a class="block {{ $navLink }}" href="#">{{ __('Link') }}</a></li>
                <li><a class="block {{ $navDisabled }}" href="#">{{ __('Disabled') }}</a></li>
            </ul>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Nav Pills with Dropdowns') }}</x-slot:header>
            <ul class="flex flex-wrap items-center gap-2">
                <li><a class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white" href="#">{{ __('Active') }}</a></li>
                <li>
                    <x-dropdown align="left">
                        <x-slot:trigger>
                            <button type="button" class="{{ $navLink }}">{{ __('Dropdown') }} <i class="ik ik-chevron-down"></i></button>
                        </x-slot:trigger>
                        <a class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50" href="#">{{ __('Action') }}</a>
                        <a class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50" href="#">{{ __('Another action') }}</a>
                        <a class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50" href="#">{{ __('Something else here') }}</a>
                        <div class="my-1 border-t border-gray-100"></div>
                        <a class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50" href="#">{{ __('Separated link') }}</a>
                    </x-dropdown>
                </li>
                <li><a class="{{ $navLink }}" href="#">{{ __('Link') }}</a></li>
                <li><a class="{{ $navDisabled }}" href="#">{{ __('Disabled') }}</a></li>
            </ul>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Pagination Basic') }}</x-slot:header>
            <nav aria-label="Page navigation example">
                <ul class="inline-flex items-center gap-1">
                    <li><a class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50" href="#"><i class="ik ik-chevrons-left"></i></a></li>
                    <li><a class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50" href="#"><i class="ik ik-chevron-left"></i></a></li>
                    <li><a class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" href="#">1</a></li>
                    <li><a class="flex h-9 w-9 items-center justify-center rounded-lg border border-primary-500 bg-primary-500 text-white" href="#">2</a></li>
                    <li><a class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50" href="#">3</a></li>
                    <li><a class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50" href="#" aria-label="Next"><i class="ik ik-chevron-right"></i></a></li>
                    <li><a class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50" href="#"><i class="ik ik-chevrons-right"></i></a></li>
                </ul>
            </nav>
        </x-card>
    </div>
@endsection
