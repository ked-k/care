@extends('layouts.main')
@section('title', 'Users')
@section('content')
    <x-page-header title="{{ __('Users') }}" subtitle="{{ __('List of users') }}" icon="ik ik-users"
                   :breadcrumbs="['Home' => route('dashboard'), 'Users' => null]" />

    <x-alert />

    <x-table :paginator="$users" title="{{ __('Users') }}" search selectable
             :add-url="auth()->user()->can('manage_user') ? url('user/create') : null" add-label="{{ __('Add User') }}">
        @can('manage_user')
            <x-slot:bulkActions>
                <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-accent-200 bg-white px-2.5 py-1.5 text-xs font-medium text-accent-600 transition hover:bg-accent-50"
                        @click="$store.confirm.open({ title: @js(__('Delete selected users?')), message: selectedCount + @js(' ' . __('users will be permanently removed.')), confirmText: @js(__('Delete')), onConfirm: () => window.toast(@js(__('Connect this to a bulk-delete route to enable.')), 'info') })">
                    <i class="ik ik-trash-2"></i> {{ __('Delete') }}
                </button>
            </x-slot:bulkActions>
        @endcan
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="w-10 px-5 py-3" data-no-export>
                        <input type="checkbox" class="row-select-all h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200" @change="toggleAll($event)" aria-label="{{ __('Select all') }}">
                    </th>
                    <th class="px-5 py-3 font-medium">{{ __('Name') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Email') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Role') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Permissions') }}</th>
                    <th class="px-5 py-3 font-medium" data-no-export>{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3" data-no-export>
                            @if ($user->name !== 'Super Admin')
                                <input type="checkbox" value="{{ $user->id }}" class="row-select h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200" @change="sync()" aria-label="{{ __('Select') }} {{ $user->name }}">
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$user->name" />
                                <span class="font-medium text-gray-700">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $user->email }}</td>
                        <td class="px-5 py-3">
                            @foreach ($user->getRoleNames() as $role)
                                <x-badge color="primary" class="mr-1">{{ $role }}</x-badge>
                            @endforeach
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($user->getAllPermissions() as $permission)
                                    <x-badge color="dark">{{ $permission->name }}</x-badge>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-3" data-no-export>
                            @if ($user->name !== 'Super Admin')
                                @can('manage_user')
                                    <div class="flex items-center gap-3">
                                        <a href="{{ url('user/' . $user->id) }}" class="text-green-500 hover:text-green-600" title="{{ __('Edit') }}"><i class="ik ik-edit-2"></i></a>
                                        <form action="{{ url('user/delete/' . $user->id) }}" method="POST" x-data>
                                            @csrf @method('DELETE')
                                            <button type="button" class="text-accent-500 hover:text-accent-600" title="{{ __('Delete') }}"
                                                    @click="$store.confirm.open({ title: @js(__('Delete user?')), message: @js(__('This will permanently remove :name. This action cannot be undone.', ['name' => $user->name])), confirmText: @js(__('Delete')), onConfirm: () => $root.submit() })">
                                                <i class="ik ik-trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">
                        <x-empty-state icon="ik ik-users" title="{{ __('No users found') }}"
                                       description="{{ request('search') ? __('No users match your search. Try a different term.') : __('Get started by adding your first user.') }}" compact />
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </x-table>
@endsection
