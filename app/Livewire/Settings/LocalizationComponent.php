<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\Setting;

class LocalizationComponent extends Component
{
    public $language = 'en_US';
    public $timezone = 'UTC';

    public function save()
    {
        Setting::set('settings.localization', ['language' => $this->language, 'timezone' => $this->timezone]);
        $this->dispatchBrowserEvent('toast', ['message' => 'Localization saved', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.settings.localization');
    }
}
