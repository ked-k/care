<?php
namespace App\Livewire\Agency;

use App\Models\Agency;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

// #[Layout('layouts.main', 'content')]
class AgencySettingsComponent extends Component
{
    public string $formName         = '';
    public string $formAddress      = '';
    public string $formContactEmail = '';
    public string $formPhone        = '';
    public string $formWeekStartsOn = 'monday';

    public function mount(): void
    {
        // abort_unless(Auth::user()->hasAnyRole(['admin', 'manager']), 403);

        $agency = $this->agency();

        $this->formName         = $agency->name;
        $this->formAddress      = $agency->address ?? '';
        $this->formContactEmail = $agency->contact_email ?? '';
        $this->formPhone        = $agency->phone ?? '';
        $this->formWeekStartsOn = $agency->settings['week_starts_on'] ?? 'monday';
    }

    protected function agency(): Agency
    {
        return Agency::findOrFail(Auth::user()->agency_id);
    }

    public function save(): void
    {
        $this->validate([
            'formName'         => 'required|string|max:255',
            'formContactEmail' => 'nullable|email|max:255',
            'formPhone'        => 'nullable|string|max:255',
        ]);

        $agency = $this->agency();

        $agency->update([
            'name'          => $this->formName,
            'address'       => $this->formAddress ?: null,
            'contact_email' => $this->formContactEmail ?: null,
            'phone'         => $this->formPhone ?: null,
            'settings'      => array_merge($agency->settings ?? [], ['week_starts_on' => $this->formWeekStartsOn]),
        ]);

        $this->dispatch('toast', message: 'Agency settings saved.', type: 'success');
    }

    public function render()
    {
        return view('livewire.agency.agency-settings');
    }
}
