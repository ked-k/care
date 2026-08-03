@extends('layouts.main')
@section('title', 'Roles')
@section('content')
    <x-page-header title="{{ __('Roles') }}" subtitle="{{ __('Define roles of user') }}" icon="ik ik-award"
                   :breadcrumbs="['Home' => route('dashboard'), 'Roles' => null]" />

    <x-alert />

    <div
        x-data="{
            mode: 'create',
            form: { id: '', name: '' },
            openForm(role) {
                if (role) {
                    this.mode = 'edit';
                    this.form = { id: role.id, name: role.name };
                    this.$dispatch('permission-set', { channel: 'role-perms', ids: role.permissions });
                } else {
                    this.mode = 'create';
                    this.form = { id: '', name: '' };
                    this.$dispatch('permission-set', { channel: 'role-perms', ids: [] });
                }
                this.$dispatch('open-drawer', 'role-form');
            },
            get actionUrl() { return this.mode === 'edit' ? '{{ url('role/update') }}' : '{{ url('role/create') }}' },
        }"
        @can('manage_role')
        x-init="@if(old('name')) openForm(); $nextTick(() => { form.name = @js(old('name')); $dispatch('permission-set', { channel: 'role-perms', ids: @js(array_map('strval', (array) old('permissions', []))) }) }); @endif"
        @endcan
    >
        <x-table :paginator="$roles" title="{{ __('Roles') }}" search>
            @can('manage_role')
                <x-slot:actions>
                    <button type="button" @click="openForm(null)"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-primary-500 to-primary-600 px-3 py-2 text-sm font-medium text-white shadow-sm shadow-primary-500/30 transition hover:from-primary-600 hover:to-primary-700">
                        <i class="ik ik-plus"></i>{{ __('Create Role') }}
                    </button>
                </x-slot:actions>
            @endcan

            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Role') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Permissions') }}</th>
                        <th class="px-5 py-3 font-medium" data-no-export>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($roles as $role)
                        @php
                            $rolePayload = [
                                'id' => $role->id,
                                'name' => clean($role->name, 'titles'),
                                'permissions' => $role->permissions->pluck('id')->map(fn ($id) => (string) $id)->values(),
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 font-semibold text-primary-600">
                                        {{ strtoupper(mb_substr($role->name, 0, 1)) }}
                                    </span>
                                    <span class="font-medium text-gray-700">{{ $role->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                @if ($role->name === 'Super Admin')
                                    <x-badge color="success">{{ __('All permissions') }}</x-badge>
                                @elseif ($role->permissions->isEmpty())
                                    <span class="text-xs text-gray-400">{{ __('No permissions') }}</span>
                                @else
                                    <div class="flex flex-wrap items-center gap-1" x-data="{ show: false }">
                                        @foreach ($role->permissions->take(6) as $permission)
                                            <x-badge color="primary">{{ $permission->name }}</x-badge>
                                        @endforeach
                                        @if ($role->permissions->count() > 6)
                                            <template x-for="p in @js($role->permissions->slice(6)->pluck('name')->values())" :key="p">
                                                <span x-show="show" class="inline-flex items-center rounded bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700" x-text="p"></span>
                                            </template>
                                            <button type="button" @click="show = !show"
                                                    class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 transition hover:bg-gray-200"
                                                    x-text="show ? '{{ __('Show less') }}' : '+{{ $role->permissions->count() - 6 }} {{ __('more') }}'"></button>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3" data-no-export>
                                @if ($role->name !== 'Super Admin')
                                    @can('manage_roles')
                                        <div class="flex items-center gap-3">
                                            <button type="button" @click="openForm(@js($rolePayload))"
                                                    class="text-green-500 transition hover:text-green-600" title="{{ __('Edit') }}"><i class="ik ik-edit-2"></i></button>
                                            <form action="{{ url('role/delete/' . $role->id) }}" method="POST" x-data>
                                                @csrf @method('DELETE')
                                                <button type="button" class="text-accent-500 transition hover:text-accent-600" title="{{ __('Delete') }}"
                                                        @click="$store.confirm.open({ title: @js(__('Delete role?')), message: @js(__('Users assigned to :name will lose it. This action cannot be undone.', ['name' => $role->name])), confirmText: @js(__('Delete')), onConfirm: () => $root.submit() })">
                                                    <i class="ik ik-trash-2"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">
                            <x-empty-state icon="ik ik-award" title="{{ __('No roles found') }}"
                                           description="{{ __('Create a role to start organising permissions.') }}" compact />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>

        {{-- Create / Edit drawer --}}
        @canany(['manage_role', 'manage_roles'])
            <x-drawer name="role-form" width="w-[42rem]">
                <x-slot:title>
                    <span class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 text-primary-600"><i class="ik ik-award"></i></span>
                        <span x-text="mode === 'edit' ? '{{ __('Edit Role') }}' : '{{ __('Create Role') }}'"></span>
                    </span>
                </x-slot:title>

                <form id="role-form-el" method="POST" :action="actionUrl" class="space-y-5">
                    @csrf
                    <input type="hidden" name="id" :value="form.id">

                    <div>
                        <label for="role-name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Role name') }} <span class="text-accent-500">*</span></label>
                        <input id="role-name" type="text" name="name" x-model="form.name" required
                               placeholder="{{ __('e.g. Store Manager') }}"
                               class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    </div>

                    <div>
                        <div class="mb-1 flex items-center gap-2">
                            <label class="block text-sm font-medium text-gray-700">{{ __('Assign permissions') }}</label>
                            <span class="text-xs text-gray-400">{{ __('Grouped by module — search or pick whole groups') }}</span>
                        </div>
                        <x-permission-selector :permissions="$permissions" channel="role-perms" />
                    </div>
                </form>

                <x-slot:footer>
                    <button type="button" @click="$dispatch('close-drawer', 'role-form')"
                            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" form="role-form-el"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-primary-500 to-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm shadow-primary-500/30 transition hover:from-primary-600 hover:to-primary-700">
                        <i class="ik ik-check"></i>
                        <span x-text="mode === 'edit' ? '{{ __('Update role') }}' : '{{ __('Save role') }}'"></span>
                    </button>
                </x-slot:footer>
            </x-drawer>
        @endcanany
    </div>
@endsection
