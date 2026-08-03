@extends('layouts.main')
@section('title', $role->name.' - Edit Role')
@section('content')
    <x-page-header title="{{ __('Edit Role') }}" subtitle="{{ __('Edit role & associate permissions') }}" icon="ik ik-award"
                   :breadcrumbs="['Home' => url('dashboard'), 'Role' => url('roles'), clean($role->name, 'titles') => null]" />

    <x-alert />

    <x-card>
        <x-slot:header>{{ __('Edit Role') }}</x-slot:header>
        <form method="POST" action="{{ url('role/update') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $role->id }}">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <x-form.input name="name" label="{{ __('Role') }}" :value="clean($role->name, 'titles')" placeholder="{{ __('Insert Role') }}" required />
                </div>
                <div class="lg:col-span-8">
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Assign Permission') }}</label>
                    <x-permission-selector :permissions="$permissions" :selected="$role_permission" />
                    <div class="mt-4">
                        <x-button type="submit">{{ __('Update') }}</x-button>
                    </div>
                </div>
            </div>
        </form>
    </x-card>
@endsection
