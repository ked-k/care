<div>
    @can('manage_role')
        <form wire:submit.prevent="updateRoles" class="space-y-3">
            <label class="block text-sm font-medium text-gray-700">{{ __('Roles') }}</label>
            <select multiple wire:model="selectedRoles" class="w-full rounded border-gray-200 p-2">
                @foreach($roles as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <div class="pt-2">
                <x-button type="submit" variant="primary">{{ __('Save roles') }}</x-button>
            </div>
        </form>
    @else
        <div class="text-sm text-gray-600">{{ __('Role information is not editable.') }}</div>
    @endcan
</div>
