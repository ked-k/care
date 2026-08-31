<?php
namespace App\Livewire\ServiceUser;

use App\Models\ServiceUser;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

// #[Layout('layouts.main', 'content')]
class ServiceUserManagerComponent extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showInactive = false;

    // Form (drawer) state
    public ?string $editingServiceUserId = null;
    public string $formName              = '';
    public string $formDob               = '';
    public string $formGender            = '';
    public string $formAddress           = '';
    public string $formNhsNumber         = '';
    public string $formNextOfKinName     = '';
    public string $formNextOfKinContact  = '';
    public bool $formConsentStatus       = false;

    public function mount(): void
    {
        // abort_unless(Auth::user()->hasAnyRole(['admin', 'manager']), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->dispatch('open-drawer', 'service-user-form');
    }

    public function openEditForm(string $serviceUserId): void
    {
        $su = ServiceUser::findOrFail($serviceUserId);

        $this->editingServiceUserId = $su->id;
        $this->formName             = $su->name;
        $this->formDob              = $su->dob?->toDateString() ?? '';
        $this->formGender           = $su->gender ?? '';
        $this->formAddress          = $su->address ?? '';
        $this->formNhsNumber        = $su->nhs_number ?? '';
        $this->formNextOfKinName    = $su->next_of_kin_name ?? '';
        $this->formNextOfKinContact = $su->next_of_kin_contact ?? '';
        $this->formConsentStatus    = (bool) $su->consent_status;
        $this->dispatch('open-drawer', 'service-user-form');
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingServiceUserId', 'formName', 'formDob', 'formGender', 'formAddress',
            'formNhsNumber', 'formNextOfKinName', 'formNextOfKinContact', 'formConsentStatus',
        ]);
    }

    public function saveServiceUser(): void
    {
        $this->validate([
            'formName'             => 'required|string|max:255',
            'formDob'              => 'nullable|date|before:today',
            'formGender'           => 'nullable|string|max:50',
            'formAddress'          => 'nullable|string',
            'formNhsNumber'        => 'nullable|string|max:50',
            'formNextOfKinName'    => 'nullable|string|max:255',
            'formNextOfKinContact' => 'nullable|string|max:255',
        ]);

        $data = [
            'agency_id'           => Auth::user()->agency_id,
            'name'                => $this->formName,
            'dob'                 => $this->formDob ?: null,
            'gender'              => $this->formGender ?: null,
            'address'             => $this->formAddress ?: null,
            'nhs_number'          => $this->formNhsNumber ?: null,
            'next_of_kin_name'    => $this->formNextOfKinName ?: null,
            'next_of_kin_contact' => $this->formNextOfKinContact ?: null,
            'consent_status'      => $this->formConsentStatus,
        ];

        if ($this->editingServiceUserId) {
            $su = ServiceUser::findOrFail($this->editingServiceUserId);
            $su->update($data);
            AuditLogger::log('SERVICE_USER_UPDATED', $su);
        } else {
            $su = ServiceUser::create($data);
            AuditLogger::log('SERVICE_USER_CREATED', $su);
        }

        $this->resetForm();
        $this->dispatch('close-drawer', 'service-user-form');
        $this->dispatch('toast', message: 'Service user saved.', type: 'success');
    }

    public function toggleActive(string $serviceUserId): void
    {
        $su = ServiceUser::withTrashed()->findOrFail($serviceUserId);

        if ($su->trashed()) {
            $su->restore();
            AuditLogger::log('SERVICE_USER_REACTIVATED', $su);
            $this->dispatch('toast', message: 'Service user reactivated.', type: 'success');
        } else {
            $su->delete();
            AuditLogger::log('SERVICE_USER_DEACTIVATED', $su);
            $this->dispatch('toast', message: 'Service user marked inactive.', type: 'success');
        }
    }

    public function render()
    {
        $agencyId = Auth::user()->agency_id;

        $serviceUsers = ServiceUser::where('agency_id', $agencyId)
            ->when($this->showInactive, fn($q) => $q->withTrashed())
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(12);

        return view('livewire.service-user.service-user-manager', compact('serviceUsers'));
    }
}
