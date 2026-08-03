@extends('layouts.main')
@section('title', 'Layouts')
@section('content')
    <x-page-header title="{{ __('Layouts') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-file-text"
                   :breadcrumbs="['Home' => route('dashboard'), 'Ui Elements' => null, 'Layouts' => null]" />

    @php
        $items = [
            ['portfolio-1.jpg', 'Donec felis urna, commodo eget velit interdum, malesuada lacinia eros.', 'Art', '02.04.2018', 'On Hold', 'gray'],
            ['portfolio-2.jpg', 'Proin sit amet augue lorem. Interdum et malesuada fames.', 'Travel', '21.03.2018', 'Processed', 'primary'],
            ['portfolio-7.jpg', 'Class aptent taciti sociosqu ad litora torquent per conubia nostra.', 'Design', '19.02.2018', 'On Hold', 'gray'],
            ['portfolio-8.jpg', 'Maecenas ut felis iaculis, dapibus mi quis, cursus mi.', 'Travel', '14.02.2018', 'Processed', 'primary'],
        ];
    @endphp

    {{-- view = 'list' | 'box' --}}
    <div x-data="{ view: 'list' }">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white p-1">
                <button type="button" @click="view = 'list'"
                        :class="view === 'list' ? 'bg-primary-50 text-primary-600' : 'text-gray-400 hover:bg-gray-100'"
                        class="flex h-8 items-center gap-1.5 rounded-md px-3 text-sm font-medium transition" title="{{ __('List view') }}">
                    <i class="ik ik-list"></i><span class="hidden sm:inline">{{ __('List') }}</span>
                </button>
                <button type="button" @click="view = 'box'"
                        :class="view === 'box' ? 'bg-primary-50 text-primary-600' : 'text-gray-400 hover:bg-gray-100'"
                        class="flex h-8 items-center gap-1.5 rounded-md px-3 text-sm font-medium transition" title="{{ __('Box view') }}">
                    <i class="ik ik-grid"></i><span class="hidden sm:inline">{{ __('Box') }}</span>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative">
                    <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" placeholder="Search.." class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                </div>
                <span class="hidden text-sm text-gray-400 sm:inline">{{ __('Displaying 1-10 of 210 items') }}</span>
            </div>
        </div>

        {{-- ===================== LIST VIEW ===================== --}}
        <div x-show="view === 'list'" class="space-y-3">
            @foreach ($items as [$img, $title, $cat, $date, $status, $color])
                <div class="flex flex-col overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm shadow-black/5 sm:flex-row">
                    <a href="{{ url('layout-edit-item') }}" class="relative shrink-0">
                        <img src="{{ asset('img/'.$img) }}" alt="{{ $title }}" class="h-40 w-full object-cover sm:h-full sm:w-48">
                        <span class="absolute left-2 top-2"><x-badge color="primary">{{ __('New') }}</x-badge></span>
                    </a>
                    <div class="flex flex-1 flex-col justify-between gap-3 p-4 md:flex-row md:items-center">
                        <a href="{{ url('layout-edit-item') }}" class="font-medium text-gray-700 hover:text-primary-600 md:w-2/5">{{ __($title) }}</a>
                        <p class="text-sm text-gray-400 md:w-1/6">{{ __($cat) }}</p>
                        <p class="text-sm text-gray-400 md:w-1/6">{{ __($date) }}</p>
                        <div class="md:w-1/6"><x-badge color="{{ $color }}">{{ __($status) }}</x-badge></div>
                        <div class="flex items-center gap-3">
                            <a href="{{ url('layout-edit-item') }}" class="text-gray-400 hover:text-primary-600"><i class="ik ik-eye"></i></a>
                            <a href="{{ url('layout-edit-item') }}" class="text-green-500 hover:text-green-600"><i class="ik ik-edit-2"></i></a>
                            <a href="#" class="text-accent-500 hover:text-accent-600"><i class="ik ik-trash-2"></i></a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ===================== BOX / GRID VIEW ===================== --}}
        <div x-show="view === 'box'" x-cloak class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($items as [$img, $title, $cat, $date, $status, $color])
                <div class="flex flex-col overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm shadow-black/5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <a href="{{ url('layout-edit-item') }}" class="relative block">
                        <img src="{{ asset('img/'.$img) }}" alt="{{ $title }}" class="h-44 w-full object-cover">
                        <span class="absolute left-2 top-2"><x-badge color="primary">{{ __('New') }}</x-badge></span>
                    </a>
                    <div class="flex flex-1 flex-col p-4">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ __($cat) }}</span>
                            <x-badge color="{{ $color }}">{{ __($status) }}</x-badge>
                        </div>
                        <a href="{{ url('layout-edit-item') }}" class="flex-1 font-medium text-gray-700 hover:text-primary-600">{{ __($title) }}</a>
                        <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3">
                            <span class="text-xs text-gray-400">{{ __($date) }}</span>
                            <div class="flex items-center gap-3">
                                <a href="{{ url('layout-edit-item') }}" class="text-gray-400 hover:text-primary-600"><i class="ik ik-eye"></i></a>
                                <a href="{{ url('layout-edit-item') }}" class="text-green-500 hover:text-green-600"><i class="ik ik-edit-2"></i></a>
                                <a href="#" class="text-accent-500 hover:text-accent-600"><i class="ik ik-trash-2"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
