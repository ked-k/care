<?php

namespace App\Livewire\Profile;

use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class UserRoleManagerComponent extends Component
{
    public ?int $userId = null;
    public array $roles = [];
    public array $selectedRoles = [];

    public function mount($userId = null)
    {
        $this->userId = $userId ?? Auth::id();
        $this->roles = Role::pluck('name', 'id')->toArray();

        if ($user = User::with('roles')->find($this->userId)) {
            $this->selectedRoles = $user->roles->pluck('id')->map(fn($v) => (string) $v)->toArray();
        }
    }

    public function updateRoles()
    {
        $this->authorizeManageRole();

        $user = User::find($this->userId);
        if (! $user) {
            $this->dispatchBrowserEvent('toast', ['message' => 'User not found', 'type' => 'danger']);
            return;
        }

        $user->syncRoles(Role::whereKey($this->selectedRoles ?? [])->get());

        $this->dispatchBrowserEvent('toast', ['message' => 'Roles updated', 'type' => 'success']);
        $this->emit('rolesUpdated');
    }

    protected function authorizeManageRole()
    {
        if (! Auth::user() || ! Auth::user()->can('manage_role')) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.profile.user-role-manager');
    }
}
