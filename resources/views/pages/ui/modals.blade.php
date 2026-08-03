@extends('layouts.main')
@section('title', 'Modals')
@section('content')
    <x-page-header title="{{ __('Modals') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-box"
        :breadcrumbs="['Home' => route('dashboard'), 'UI' => '#', 'Advanced' => '#', 'Modals' => null]" />

    <div x-data="{ demo: false, long: false, center: false, full: false }">
        <x-card>
            <x-slot:header>{{ __('Modals') }}</x-slot:header>
            <div class="flex flex-wrap items-center gap-2">
                <x-button variant="primary" x-on:click="demo = true">{{ __('Launch Demo Modal') }}</x-button>
                <x-button variant="secondary" x-on:click="long = true">{{ __('Scrolling Long Content') }}</x-button>
                <x-button variant="success" x-on:click="center = true">{{ __('Vertically Centered') }}</x-button>
                <x-button variant="danger" x-on:click="full = true">{{ __('Full Window') }}</x-button>
            </div>
        </x-card>

        {{-- Demo Modal --}}
        <template x-teleport="body">
            <div x-show="demo" x-cloak class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-20" x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" x-on:click="demo = false"></div>
                <div class="relative z-10 w-full max-w-lg rounded-xl bg-white shadow-xl" x-on:click.outside="demo = false">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h5 class="font-semibold text-gray-700">{{ __('Modal title') }}</h5>
                        <button type="button" x-on:click="demo = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
                    </div>
                    <div class="px-5 py-4 text-sm text-gray-600"><p>{{ __('Modal body text goes here.') }}</p></div>
                    <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                        <x-button variant="secondary" x-on:click="demo = false">{{ __('Close') }}</x-button>
                        <x-button variant="primary">{{ __('Save changes') }}</x-button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Long Content Modal --}}
        <template x-teleport="body">
            <div x-show="long" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 pt-20" x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" x-on:click="long = false"></div>
                <div class="relative z-10 my-4 w-full max-w-lg rounded-xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h5 class="font-semibold text-gray-700">{{ __('Modal title') }}</h5>
                        <button type="button" x-on:click="long = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
                    </div>
                    <div class="space-y-3 px-5 py-4 text-sm text-gray-600">
                        @for ($i = 0; $i < 6; $i++)
                            <p>{{ __('Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.') }}</p>
                            <p>{{ __('Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.') }}</p>
                        @endfor
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                        <x-button variant="secondary" x-on:click="long = false">{{ __('Close') }}</x-button>
                        <x-button variant="primary">{{ __('Save changes') }}</x-button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Vertically Centered Modal --}}
        <template x-teleport="body">
            <div x-show="center" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" x-on:click="center = false"></div>
                <div class="relative z-10 w-full max-w-lg rounded-xl bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h5 class="font-semibold text-gray-700">{{ __('Modal title') }}</h5>
                        <button type="button" x-on:click="center = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
                    </div>
                    <div class="px-5 py-4 text-sm text-gray-600"><p>{{ __('Modal body text goes here.') }}</p></div>
                    <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                        <x-button variant="secondary" x-on:click="center = false">{{ __('Close') }}</x-button>
                        <x-button variant="primary">{{ __('Save changes') }}</x-button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Full Window Modal --}}
        <template x-teleport="body">
            <div x-show="full" x-cloak class="fixed inset-0 z-50 flex flex-col bg-white" x-transition.opacity>
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h5 class="font-semibold text-gray-700">{{ __('Modal title') }}</h5>
                    <button type="button" x-on:click="full = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
                </div>
                <div class="flex-1 px-5 py-4 text-sm text-gray-600"><p>{{ __('Modal body text goes here.') }}</p></div>
                <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                    <x-button variant="secondary" x-on:click="full = false">{{ __('Close') }}</x-button>
                    <x-button variant="primary">{{ __('Save changes') }}</x-button>
                </div>
            </div>
        </template>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5">
        @foreach (['Default Modal' => 'max-w-lg', 'Large Modal' => 'max-w-3xl', 'Small Modal' => 'max-w-sm'] as $title => $width)
            <x-card>
                <x-slot:header>{{ __($title) }}</x-slot:header>
                <div class="{{ $width }} rounded-xl border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h5 class="font-semibold text-gray-700">{{ __('Modal title') }}</h5>
                        <button type="button" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
                    </div>
                    <div class="px-5 py-4 text-sm text-gray-600"><p>{{ __('Modal body text goes here.') }}</p></div>
                    <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                        <x-button variant="secondary">{{ __('Save changes') }}</x-button>
                        <x-button variant="primary">{{ __('Close') }}</x-button>
                    </div>
                </div>
            </x-card>
        @endforeach
    </div>
@endsection
