<?php

namespace App\Livewire\Family;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.family')]
class FamilyPortalComponent extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user()->hasRole('Family'), 403);
    }

    public function render()
    {
        $links = Auth::user()->familyLinks()->with('serviceUser')->get();

        return view('livewire.family.family-portal', ['links' => $links]);
    }
}
