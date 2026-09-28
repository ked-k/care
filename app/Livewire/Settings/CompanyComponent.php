<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\Setting;

class CompanyComponent extends Component
{
    public $legal_name = '';
    public $trading_name = '';
    public $phone = '';

    public function mount()
    {
        $data = Setting::get('settings.company', []);
        $this->legal_name = $data['legal_name'] ?? '';
        $this->trading_name = $data['trading_name'] ?? '';
        $this->phone = $data['phone'] ?? '';
    }

    public function save()
    {
        Setting::set('settings.company', ['legal_name' => $this->legal_name, 'trading_name' => $this->trading_name, 'phone' => $this->phone]);
        $this->dispatchBrowserEvent('toast', ['message' => 'Company settings saved', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.settings.company');
    }
}
