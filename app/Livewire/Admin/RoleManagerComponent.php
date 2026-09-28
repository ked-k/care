<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleManagerComponent extends Component
{
    use WithPagination;

    public $name = '';
    public $editId = null;
    public $permissions = [];

    protected $rules = [
        'name' => 'required|string|max:255',
    ];

    public function mount()
    {
        $this->permissions = Permission::pluck('name', 'id')->toArray();
    }

    public function createRole()
    {
        $this->validate();
        $role = Role::create(['name' => $this->name]);
        $role->syncPermissions(Permission::whereKey($this->permissions ?? [])->get());
        $this->resetForm();
        $this->dispatchBrowserEvent('toast', ['message' => 'Role created', 'type' => 'success']);
    }

    public function editRole($id)
    {
        $role = Role::with('permissions')->find($id);
        if (! $role) return;
        $this->editId = $role->id;
        $this->name = $role->name;
        $this->permissions = $role->permissions->pluck('id')->map(fn($v) => (string)$v)->toArray();
    }

    public function updateRole()
    {
        $this->validate();
        $role = Role::find($this->editId);
        if (! $role) return;
        $role->update(['name' => $this->name]);
        $role->syncPermissions(Permission::whereKey($this->permissions ?? [])->get());
        $this->resetForm();
        $this->dispatchBrowserEvent('toast', ['message' => 'Role updated', 'type' => 'success']);
    }

    public function deleteRole($id)
    {
        if ($role = Role::find($id)) {
            $role->delete();
            $this->dispatchBrowserEvent('toast', ['message' => 'Role deleted', 'type' => 'success']);
        }
    }

    protected function resetForm()
    {
        $this->name = '';
        $this->editId = null;
        $this->permissions = Permission::pluck('name', 'id')->toArray();
    }

    public function render()
    {
        $roles = Role::with('permissions')->paginate(10);
        return view('livewire.admin.role-manager', compact('roles'));
    }
}
