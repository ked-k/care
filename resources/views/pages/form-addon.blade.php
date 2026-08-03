@extends('layouts.main')
@section('title', 'Forms Group Add-Ons')
@section('content')
    <x-page-header title="{{ __('Group Add-Ons') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-edit"
                   :breadcrumbs="['Home' => route('dashboard'), 'Forms' => null, 'Group Add-Ons' => null]" />

    @php
        $addon = 'inline-flex items-center border border-gray-200 bg-gray-50 px-3 text-sm text-gray-500';
        $field = 'min-w-0 flex-1 border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100';
    @endphp

    <x-card>
        <x-slot:header>{{ __('Input Group') }}</x-slot:header>
        <h4 class="mb-4 font-semibold text-gray-700">{{ __('Basic Group Add-ons') }}</h4>
        <div class="space-y-4">
            @foreach ([
                [__('Simple Add-on'), 'left', '@', null],
                [__('Placeholder'), 'left', '%', 'Left addon'],
                [__('Right Add-on'), 'right', '$', 'Right addon'],
                [__('Both-side Add-on'), 'both', ['$', '.20'], 'Both-side addon'],
            ] as [$lbl, $pos, $sym, $ph])
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-6 sm:items-center">
                    <label class="text-sm font-medium text-gray-700 sm:col-span-2 lg:col-span-1">{{ $lbl }}</label>
                    <div class="flex sm:col-span-4 lg:col-span-5">
                        @if ($pos === 'left' || $pos === 'both')
                            <span class="{{ $addon }} rounded-l-lg">{{ is_array($sym) ? $sym[0] : $sym }}</span>
                        @endif
                        <input type="text" placeholder="{{ $ph }}" @class([$field, 'rounded-l-lg' => $pos === 'right', 'rounded-r-lg' => $pos === 'left'])>
                        @if ($pos === 'right' || $pos === 'both')
                            <span class="{{ $addon }} rounded-r-lg">{{ is_array($sym) ? $sym[1] : $sym }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-6 sm:items-center">
                <label class="text-sm font-medium text-gray-700 sm:col-span-2 lg:col-span-1">{{ __('Muliple Add-ons') }}</label>
                <div class="flex sm:col-span-4 lg:col-span-5">
                    <span class="{{ $addon }} rounded-l-lg">$</span>
                    <span class="{{ $addon }} border-l-0">.20</span>
                    <input type="text" placeholder="Multiple addons" class="{{ $field }} rounded-r-lg">
                </div>
            </div>
        </div>

        <h4 class="mb-4 mt-8 font-semibold text-gray-700">{{ __('Icon Group Addons') }}</h4>
        <div class="space-y-4">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-6 sm:items-center">
                <label class="text-sm font-medium text-gray-700 sm:col-span-2 lg:col-span-1">{{ __('Left Icon') }}</label>
                <div class="flex sm:col-span-4 lg:col-span-5">
                    <span class="{{ $addon }} rounded-l-lg"><i class="ik ik-volume"></i></span>
                    <input type="text" placeholder="Left addon" class="{{ $field }} rounded-r-lg">
                </div>
            </div>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-6 sm:items-center">
                <label class="text-sm font-medium text-gray-700 sm:col-span-2 lg:col-span-1">{{ __('Right Icon') }}</label>
                <div class="flex sm:col-span-4 lg:col-span-5">
                    <input type="text" placeholder="Right addon" class="{{ $field }} rounded-l-lg">
                    <span class="{{ $addon }} rounded-r-lg"><i class="ik ik-wifi"></i></span>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-6 sm:items-center">
                <label class="text-sm font-medium text-gray-700 sm:col-span-2 lg:col-span-1">{{ __('Both-side Icons Addon') }}</label>
                <div class="flex sm:col-span-4 lg:col-span-5">
                    <span class="{{ $addon }} rounded-l-lg"><i class="ik ik-chevron-left"></i></span>
                    <input type="text" placeholder="Right add-on" class="{{ $field }}">
                    <span class="{{ $addon }} rounded-r-lg"><i class="ik ik-chevron-right"></i></span>
                </div>
            </div>
        </div>
    </x-card>

    <div class="mt-5">
        <x-card>
            <x-slot:header>{{ __('Input Group Colors') }}</x-slot:header>
            <h4 class="mb-4 font-semibold text-gray-700">{{ __('Color Addons') }}</h4>
            @php
                $coloredAddon = 'inline-flex items-center px-3 text-sm font-medium text-white';
            @endphp
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ([
                    ['ik ik-tv', __('Primary'), 'bg-primary-500', 'focus:border-primary-500 focus:ring-primary-100', 'left'],
                    ['ik ik-volume-x', __('Success'), 'bg-green-500', 'focus:border-green-500 focus:ring-green-100', 'right'],
                    ['ik ik-gitlab', __('Warning'), 'bg-amber-500', 'focus:border-amber-500 focus:ring-amber-100', 'left'],
                    ['ik ik-volume-1', __('Danger'), 'bg-accent-500', 'focus:border-accent-500 focus:ring-accent-100', 'right'],
                    ['ik ik-bar-chart-line', __('Info'), 'bg-cyan-500', 'focus:border-cyan-500 focus:ring-cyan-100', 'left'],
                    ['ik ik-shield', __('Dark'), 'bg-slate-700', 'focus:border-slate-700 focus:ring-slate-200', 'right'],
                ] as [$icon, $lbl, $bg, $focus, $pos])
                    <div class="flex">
                        @if ($pos === 'left')
                            <span class="{{ $coloredAddon }} {{ $bg }} rounded-l-lg"><i class="{{ $icon }} mr-2"></i>{{ $lbl }}</span>
                            <input type="text" placeholder="{{ $lbl }}" class="min-w-0 flex-1 rounded-r-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 {{ $focus }}">
                        @else
                            <input type="text" placeholder="{{ $lbl }}" class="min-w-0 flex-1 rounded-l-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 {{ $focus }}">
                            <span class="{{ $coloredAddon }} {{ $bg }} rounded-r-lg">{{ $lbl }}<i class="{{ $icon }} ml-2"></i></span>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    <div class="mt-5">
        <x-card>
            <x-slot:header>{{ __('Input Group With Components') }}</x-slot:header>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div>
                    <h4 class="mb-4 font-semibold text-gray-700">{{ __('Icon Group with Buttons') }}</h4>
                    <div class="space-y-4">
                        <div class="flex">
                            <x-button class="rounded-r-none">{{ __('Left Button') }}</x-button>
                            <input type="text" placeholder="Left Button" class="{{ $field }} rounded-r-lg">
                        </div>
                        <div class="flex">
                            <input type="text" placeholder="Right Button" class="{{ $field }} rounded-l-lg">
                            <x-button class="rounded-l-none">{{ __('Right Button') }}</x-button>
                        </div>
                        <div class="flex">
                            <x-button class="rounded-r-none">{{ __('Left Button') }}</x-button>
                            <input type="text" placeholder="Both side addons" class="{{ $field }}">
                            <x-button class="rounded-l-none">{{ __('Right Button') }}</x-button>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="mb-4 font-semibold text-gray-700">{{ __('Icon Group with Dropdowns') }}</h4>
                    <div class="space-y-4">
                        <div class="flex">
                            <x-button variant="secondary" class="rounded-r-none">{{ __('Left Action') }} <i class="ik ik-chevron-down"></i></x-button>
                            <input type="text" class="{{ $field }} rounded-r-lg">
                        </div>
                        <div class="flex">
                            <input type="text" class="{{ $field }} rounded-l-lg">
                            <x-button variant="secondary" class="rounded-l-none">{{ __('Right Action') }} <i class="ik ik-chevron-down"></i></x-button>
                        </div>
                        <div class="flex">
                            <x-button variant="secondary" class="rounded-r-none">{{ __('Click') }} <i class="ik ik-chevron-down"></i></x-button>
                            <input type="text" class="{{ $field }}">
                            <x-button variant="secondary" class="rounded-l-none">{{ __('Click') }} <i class="ik ik-chevron-down"></i></x-button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <h4 class="mb-4 font-semibold text-gray-700">{{ __('Icon Group with Checkbox') }}</h4>
                    <div class="flex">
                        <span class="{{ $addon }} rounded-l-lg"><x-form.checkbox name="addonCheckbox" /></span>
                        <input type="text" class="{{ $field }} rounded-r-lg">
                    </div>
                </div>
                <div>
                    <h4 class="mb-4 font-semibold text-gray-700">{{ __('Icon Group with Radio') }}</h4>
                    <div class="flex">
                        <span class="{{ $addon }} rounded-l-lg"><x-form.checkbox name="addonRadio" type="radio" /></span>
                        <input type="text" class="{{ $field }} rounded-r-lg">
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <div class="mt-5">
        <x-card>
            <x-slot:header>{{ __('Input Group Alignment') }}</x-slot:header>
            <div class="space-y-4">
                @foreach ([
                    [__('Normal Text'), 'ik ik-volume', '', 'Sample text', null],
                    [__('Bold Text'), 'ik ik-gitlab', 'font-bold', 'Bold text', null],
                    [__('Italic Text'), 'ik ik-edit', 'italic', 'Italic text', null],
                    [__('Capitalize Text'), 'ik ik-tv', 'capitalize', 'capitalize text', null],
                    [__('Uppercase Text'), 'ik ik-wifi', 'uppercase', 'uppercase text', null],
                    [__('Lowercase Text'), 'ik ik-shield', 'lowercase', 'LOWERCASE TEXT', null],
                    [__('Wide Spacing'), 'ik ik-volume', 'tracking-widest', 'Spaced text', null],
                    [__('Left-Align Text'), 'ik ik-tv', 'text-left', 'Left aligned', null],
                    [__('Center-Align Text'), 'ik ik-gitlab', 'text-center', 'Center aligned', null],
                    [__('Right-Align Text'), 'ik ik-shield', 'text-right', 'Right aligned', null],
                    [__('RTL Text'), 'ik ik-volume', 'text-right', 'مرحبا بالعالم', 'rtl'],
                ] as [$lbl, $icon, $util, $ph, $dir])
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-6 sm:items-center">
                        <label class="text-sm font-medium text-gray-700 sm:col-span-2 lg:col-span-1">{{ $lbl }}</label>
                        <div class="flex sm:col-span-4 lg:col-span-5">
                            <span class="{{ $addon }} rounded-l-lg"><i class="{{ $icon }}"></i></span>
                            <input type="text" value="{{ $ph }}" @if ($dir) dir="{{ $dir }}" @endif class="{{ $field }} rounded-r-lg {{ $util }}">
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>
@endsection
