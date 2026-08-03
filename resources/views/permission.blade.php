@extends('layouts.main')
@section('title', 'Permission')
@section('content')
    <x-page-header title="{{ __('Permissions') }}" subtitle="{{ __('Define permissions of user') }}" icon="ik ik-unlock"
                   :breadcrumbs="['Home' => route('dashboard'), 'Permissions' => null]" />

    <x-alert />

    @php
        // Colour palette so each role pill reads distinctly (mirrors the permission selector).
        $rolePalette = [
            'border-primary-300 bg-primary-50 text-primary-700',
            'border-violet-300 bg-violet-50 text-violet-700',
            'border-emerald-300 bg-emerald-50 text-emerald-700',
            'border-amber-300 bg-amber-50 text-amber-700',
            'border-rose-300 bg-rose-50 text-rose-700',
            'border-sky-300 bg-sky-50 text-sky-700',
            'border-indigo-300 bg-indigo-50 text-indigo-700',
        ];
    @endphp

    <div
        x-data="{
            name: '',
            roles: [],
            roleTotal: {{ $roles->count() }},
            openCreate() { this.name = ''; this.roles = []; this.$dispatch('open-drawer', 'permission-form'); },
            selectAll() { this.roles = @js($roles->keys()->map(fn ($k) => (string) $k)->values()); },
            clearAll() { this.roles = []; },
        }"
        @can('manage_permission')
        x-init="@if(old('name')) openCreate(); $nextTick(() => { name = @js(old('name')); roles = @js(array_map('strval', (array) old('roles', []))) }); @endif"
        @endcan
    >
        <x-table :paginator="$permissions" title="{{ __('Permissions') }}" search>
            @can('manage_permission')
                <x-slot:actions>
                    <button type="button" @click="openCreate()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-primary-500 to-primary-600 px-3 py-2 text-sm font-medium text-white shadow-sm shadow-primary-500/30 transition hover:from-primary-600 hover:to-primary-700">
                        <i class="ik ik-plus"></i>{{ __('Create Permission') }}
                    </button>
                </x-slot:actions>
            @endcan

            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Permission') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Assigned Role') }}</th>
                        <th class="px-5 py-3 font-medium" data-no-export>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($permissions as $permission)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                                        <i class="ik ik-lock"></i>
                                    </span>
                                    <div>
                                        <span class="block font-medium leading-tight text-gray-700">{{ ucwords(str_replace(['_', '-', '.'], ' ', $permission->name)) }}</span>
                                        <span class="block text-xs text-gray-400">{{ $permission->name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                @if ($permission->roles->isEmpty())
                                    <span class="text-xs text-gray-400">{{ __('Not assigned') }}</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($permission->roles as $role)
                                            <x-badge color="primary">{{ $role->name }}</x-badge>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3" data-no-export>
                                @can('manage_permission')
                                    <form action="{{ url('permission/delete/' . $permission->id) }}" method="POST" x-data>
                                        @csrf @method('DELETE')
                                        <button type="button" class="text-accent-500 transition hover:text-accent-600" title="{{ __('Delete') }}"
                                                @click="$store.confirm.open({ title: @js(__('Delete permission?')), message: @js(__('This action cannot be undone.')), confirmText: @js(__('Delete')), onConfirm: () => $root.submit() })">
                                            <i class="ik ik-trash-2"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">
                            <x-empty-state icon="ik ik-unlock" title="{{ __('No permissions found') }}"
                                           description="{{ __('Create a permission to start assigning it to roles.') }}" compact />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>

        {{-- Create drawer --}}
        @can('manage_permission')
            <x-drawer name="permission-form" width="w-[34rem]">
                <x-slot:title>
                    <span class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><i class="ik ik-unlock"></i></span>
                        {{ __('Create Permission') }}
                    </span>
                </x-slot:title>

                <form id="permission-form-el" method="POST" action="{{ url('permission/create') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="permission-name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Permission name') }} <span class="text-accent-500">*</span></label>
                        <input id="permission-name" type="text" name="name" x-model="name" required
                               placeholder="{{ __('e.g. manage_inventory') }}"
                               class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <p class="mt-1 text-xs text-gray-400">{{ __('Use the action_resource format (e.g. view_invoice) so it groups neatly when assigning to roles.') }}</p>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="block text-sm font-medium text-gray-700">{{ __('Assign to roles') }}</label>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-100 px-3 py-1 text-xs font-medium text-primary-700">
                                <span x-text="roles.length"></span> / <span x-text="roleTotal"></span>
                            </span>
                        </div>

                        @if ($roles->isEmpty())
                            <p class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-3 py-4 text-center text-sm text-gray-400">
                                {{ __('No roles yet — create a role first to assign this permission.') }}
                            </p>
                        @else
                            <div class="mb-2 flex items-center gap-1.5">
                                <button type="button" @click="selectAll()"
                                        class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700">
                                    {{ __('Select all') }}
                                </button>
                                <button type="button" @click="clearAll()"
                                        class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50"
                                        :class="roles.length === 0 && 'pointer-events-none opacity-50'">
                                    {{ __('Clear') }}
                                </button>
                            </div>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach ($roles as $roleId => $roleName)
                                    @php $rc = $rolePalette[$loop->index % count($rolePalette)]; @endphp
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border px-3 py-2.5 text-sm transition"
                                           :class="roles.includes('{{ $roleId }}') ? '{{ $rc }} shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50'">
                                        <input type="checkbox" name="roles[]" value="{{ $roleId }}" x-model="roles"
                                               class="h-4 w-4 shrink-0 rounded border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-200">
                                        <span class="font-medium">{{ $roleName }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </form>

                <x-slot:footer>
                    <button type="button" @click="$dispatch('close-drawer', 'permission-form')"
                            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" form="permission-form-el"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-primary-500 to-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm shadow-primary-500/30 transition hover:from-primary-600 hover:to-primary-700">
                        <i class="ik ik-check"></i>{{ __('Save permission') }}
                    </button>
                </x-slot:footer>
            </x-drawer>
        @endcan
    </div>
@endsection
