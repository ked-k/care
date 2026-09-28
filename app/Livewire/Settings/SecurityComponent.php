<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\Setting;

class SecurityComponent extends Component
{
    public $two_factor = false;

    public function save()
    {
        Setting::set('settings.security', ['two_factor' => $this->two_factor]);
        $this->dispatchBrowserEvent('toast', ['message' => 'Security settings saved', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.settings.security');
    }
}
