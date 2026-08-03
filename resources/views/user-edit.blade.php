@extends('layouts.main')
@section('title', $user->name)
@section('content')
    <x-page-header title="{{ __('Edit User') }}" subtitle="{{ __('Update user, assign roles & permissions') }}" icon="ik ik-user-plus"
                   :breadcrumbs="['Home' => url('/'), 'User' => url('users'), clean($user->name, 'titles') => null]" />

    <x-alert />

    <x-card>
        <form method="POST" action="{{ url('user/update') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $user->id }}">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-4">
                    <x-form.input name="name" label="{{ __('Username') }}" :value="clean($user->name, 'titles')" required />
                    <x-form.input name="email" type="email" label="{{ __('Email') }}" :value="clean($user->email, 'titles')" required />
                    <x-form.input name="password" type="password" label="{{ __('Password') }}" placeholder="{{ __('Leave blank to keep current') }}" />
                    <x-form.input name="password_confirmation" type="password" label="{{ __('Confirm Password') }}" />
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="role" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Assign Role') }}<span class="text-accent-500">*</span></label>
                        <select name="role" id="role" required
                                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <option value="">{{ __('Select Role') }}</option>
                            @foreach ($roles as $roleId => $roleName)
                                <option value="{{ $roleId }}" @selected(($user_role->id ?? '') == $roleId)>{{ $roleName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Permissions') }}</label>
                        <div class="min-h-[60px] rounded-lg border-l-2 border-gray-200 bg-gray-50 p-3">
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->getAllPermissions() as $permission)
                                    <x-badge color="dark">{{ clean($permission->name, 'titles') }}</x-badge>
                                @empty
                                    <span class="text-sm text-gray-400">{{ __('No permissions') }}</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <x-button type="submit">{{ __('Update') }}</x-button>
                </div>
            </div>
        </form>
    </x-card>
@endsection
