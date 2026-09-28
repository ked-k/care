<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\Setting;

class NotificationsComponent extends Component
{
    public $email_new_order = true;
    public $push_new_order = true;

    public function save()
    {
        Setting::set('settings.notifications', ['email_new_order' => $this->email_new_order, 'push_new_order' => $this->push_new_order]);
        $this->dispatchBrowserEvent('toast', ['message' => 'Notification settings saved', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.settings.notifications');
    }
}
