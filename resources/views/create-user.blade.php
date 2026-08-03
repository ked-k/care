@extends('layouts.main')
@section('title', 'Add User')
@section('content')
    <x-page-header title="{{ __('Add User') }}" subtitle="{{ __('Create new user, assign roles & permissions') }}" icon="ik ik-user-plus"
                   :breadcrumbs="['Home' => url('dashboard'), 'Add User' => null]" />

    <x-alert />

    <x-card>
        <x-slot:header>{{ __('Add user') }}</x-slot:header>
        <form method="POST" action="{{ route('create-user') }}"
              x-data="{ badges: '', loading: false,
                        async loadPermissions(id) {
                            if (!id) { this.badges = ''; return; }
                            this.loading = true;
                            try { const { data } = await window.axios.get('{{ url('get-role-permissions-badge') }}', { params: { id } }); this.badges = data; }
                            finally { this.loading = false; }
                        } }">
            @csrf
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-4">
                    <x-form.input name="name" label="{{ __('Username') }}" placeholder="{{ __('Enter user name') }}" required />
                    <x-form.input name="email" type="email" label="{{ __('Email') }}" placeholder="{{ __('Enter email address') }}" required />
                    <x-form.input name="password" type="password" label="{{ __('Password') }}" placeholder="{{ __('Enter password') }}" required />
                    <x-form.input name="password_confirmation" type="password" label="{{ __('Confirm Password') }}" placeholder="{{ __('Retype password') }}" required />
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="role" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Assign Role') }}<span class="text-accent-500">*</span></label>
                        <select name="role" id="role" required @change="loadPermissions($event.target.value)"
                                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <option value="">{{ __('Select Role') }}</option>
                            @foreach ($roles as $roleId => $roleName)
                                <option value="{{ $roleId }}">{{ $roleName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Permissions') }}</label>
                        <div class="min-h-[60px] rounded-lg border-l-2 border-gray-200 bg-gray-50 p-3">
                            <span x-show="loading" class="text-sm text-gray-400">{{ __('Loading...') }}</span>
                            <span x-show="!loading && !badges" class="text-sm text-accent-500">{{ __('Select role first') }}</span>
                            <div x-show="!loading" x-html="badges" class="flex flex-wrap"></div>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <x-button type="submit">{{ __('Submit') }}</x-button>
                </div>
            </div>
        </form>
    </x-card>
@endsection
