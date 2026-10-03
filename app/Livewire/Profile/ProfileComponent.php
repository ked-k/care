<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProfileComponent extends Component
{
    public $name;
    public $email;
    public $phone;
    public $address;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1024',
        ];
    }

    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->address = $user->address ?? '';
    }

    public function updateProfile()
    {
        $this->validate();

        $user = Auth::user();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->phone = $this->phone;
        $user->address = $this->address;
        $user->save();

        $this->dispatchBrowserEvent('toast', ['message' => 'Profile updated', 'type' => 'success']);
    }

    public function render()
    {
        // Batch 12: a plain carer gets the mobile carer shell (no admin
        // sidebar) for their own profile page too — see
        // App\Models\User::isCarerOnly(). Content is unchanged either way.
        return view('livewire.profile.profile')
            ->layout(Auth::user()->isCarerOnly() ? 'layouts.carer' : 'layouts.admin-layout');
    }
}
