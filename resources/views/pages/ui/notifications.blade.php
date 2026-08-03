@extends('layouts.main')
@section('title', 'Notifications')
@section('content')
    <x-page-header title="{{ __('Notifications') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-gitlab"
        :breadcrumbs="['Home' => route('dashboard'), 'UI' => '#', 'Advanced' => '#', 'Notifications' => null]" />

    <div x-data="{
            toasts: [],
            push(type, position) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, type, position });
                setTimeout(() => this.remove(id), 3500);
            },
            remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); },
            styles: {
                success: 'border-green-200 bg-green-50 text-green-700',
                info: 'border-primary-200 bg-primary-50 text-primary-700',
                warning: 'border-amber-200 bg-amber-50 text-amber-700',
                danger: 'border-accent-500/30 bg-accent-500/10 text-accent-600',
            },
            positions: {
                'top-left': 'top-4 left-4', 'top-center': 'top-4 left-1/2 -translate-x-1/2', 'top-right': 'top-4 right-4',
                'mid-center': 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2',
                'bottom-left': 'bottom-4 left-4', 'bottom-center': 'bottom-4 left-1/2 -translate-x-1/2', 'bottom-right': 'bottom-4 right-4',
            }
        }">
        <div class="grid grid-cols-1 gap-5">
            <x-card>
                <x-slot:header>{{ __('Toast Styles') }}</x-slot:header>
                <p class="mb-4 text-sm text-gray-600">Click on the below buttons for notifications in different styles.</p>
                <div class="flex flex-wrap items-center gap-2">
                    <x-button variant="success" x-on:click="push('success', 'top-right')">{{ __('Success') }}</x-button>
                    <x-button variant="primary" x-on:click="push('info', 'top-right')">{{ __('Info') }}</x-button>
                    <x-button variant="warning" x-on:click="push('warning', 'top-right')">{{ __('Warning') }}</x-button>
                    <x-button variant="danger" x-on:click="push('danger', 'top-right')">{{ __('Danger') }}</x-button>
                </div>
            </x-card>

            <x-card>
                <x-slot:header>{{ __('Toast Positions') }}</x-slot:header>
                <p class="mb-4 text-sm text-gray-600">Click a button to show a toast in the chosen position.</p>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['bottom-left' => 'Bottom Left', 'bottom-right' => 'Bottom Right', 'bottom-center' => 'Bottom Center', 'top-left' => 'Top Left', 'top-right' => 'Top Right', 'top-center' => 'Top Center', 'mid-center' => 'Mid Center'] as $pos => $label)
                        <x-button variant="primary" size="sm" x-on:click="push('info', '{{ $pos }}')">{{ __($label) }}</x-button>
                    @endforeach
                </div>
            </x-card>
        </div>

        <template x-for="toast in toasts" :key="toast.id">
            <div class="fixed z-50 w-72" :class="positions[toast.position]" x-transition>
                <div class="flex items-center justify-between gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg" :class="styles[toast.type]">
                    <span x-text="toast.type.charAt(0).toUpperCase() + toast.type.slice(1) + ' notification!'"></span>
                    <button type="button" x-on:click="remove(toast.id)" class="opacity-60 hover:opacity-100"><i class="ik ik-x"></i></button>
                </div>
            </div>
        </template>
    </div>
@endsection
