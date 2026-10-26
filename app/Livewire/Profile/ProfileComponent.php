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
        // Batch 39: this read `$user->phone`, a column that has never
        // existed (the real one is `phone_number`) — see the Batch 39
        // migration's doc comment on `address` for why this page's save
        // has been failing outright, not just losing this one field.
        $this->phone = $user->phone_number ?? '';
        $this->address = $user->address ?? '';
    }

    public function updateProfile()
    {
        $this->validate();

        $user = Auth::user();
        $user->name = $this->name;
        $user->email = $this->email;
        $user->phone_number = $this->phone;
        $user->address = $this->address;
        $user->save();

        // Batch 39: dispatchBrowserEvent() is the Livewire v2 API — every
        // other toast in this app fires via dispatch() (Livewire v3), which
        // is what the shared x-on:toast.window listener actually expects.
        $this->dispatch('toast', message: 'Profile updated', type: 'success');
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
