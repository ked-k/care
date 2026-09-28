<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\Setting;

class AppearanceComponent extends Component
{
    public $dark_mode = false;
    public $accent = 'primary';

    public function mount()
    {
        $data = Setting::get('settings.appearance', []);
        $this->dark_mode = $data['dark_mode'] ?? false;
        $this->accent = $data['accent'] ?? 'primary';
    }

    public function save()
    {
        Setting::set('settings.appearance', ['dark_mode' => $this->dark_mode, 'accent' => $this->accent]);
        $this->dispatchBrowserEvent('toast', ['message' => 'Appearance saved', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.settings.appearance');
    }
}
