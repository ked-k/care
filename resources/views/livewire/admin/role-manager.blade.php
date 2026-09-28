<div>
    <x-page-header title="{{ __('Roles') }}" subtitle="{{ __('Manage roles & permissions') }}" icon="ik ik-award"
                   :breadcrumbs="['Home' => route('dashboard'), 'Roles' => null]" />

    <x-card>
        <x-slot:header>{{ __('Create / Edit Role') }}</x-slot:header>
        <form wire:submit.prevent="{{ $editId ? 'updateRole' : 'createRole' }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <input type="text" wire:model.defer="name" placeholder="Role name" class="col-span-2" />
                <div class="col-span-1">
                    <button type="submit" class="btn btn-primary">{{ $editId ? __('Update') : __('Create') }}</button>
                </div>
            </div>
            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-700">{{ __('Permissions') }}</label>
                <select multiple wire:model="permissions" class="w-full border-gray-200 p-2 rounded">
                    @foreach(
                        Spatie\Permission\Models\Permission::pluck('name','id') as $id => $label
                    )
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </x-card>

    <x-card class="mt-4">
        <x-slot:header>{{ __('Existing Roles') }}</x-slot:header>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500 uppercase"><th>{{ __('Role') }}</th><th>{{ __('Permissions') }}</th><th></th></tr>
            </thead>
            <tbody class="divide-y">
                @foreach($roles as $role)
                    <tr>
                        <td class="py-2 font-medium">{{ $role->name }}</td>
                        <td class="py-2 text-gray-600">{{ $role->permissions->pluck('name')->join(', ') }}</td>
                        <td class="py-2 text-right">
                            <button wire:click="editRole({{ $role->id }})" class="mr-2">{{ __('Edit') }}</button>
                            <button wire:click="deleteRole({{ $role->id }})" class="text-red-500">{{ __('Delete') }}</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-3">{{ $roles->links() }}</div>
    </x-card>
</div>
