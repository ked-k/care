<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\Setting;

class GeneralComponent extends Component
{
    public $app_name;
    public $support_email;
    public $contact_phone;

    public function mount()
    {
        $data = Setting::get('settings.general', []);
        $this->app_name = $data['app_name'] ?? config('app.name');
        $this->support_email = $data['support_email'] ?? config('mail.from.address');
        $this->contact_phone = $data['contact_phone'] ?? '';
    }

    public function save()
    {
        Setting::set('settings.general', [
            'app_name' => $this->app_name,
            'support_email' => $this->support_email,
            'contact_phone' => $this->contact_phone,
        ]);

        $this->dispatchBrowserEvent('toast', ['message' => 'General settings saved', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.settings.general');
    }
}
