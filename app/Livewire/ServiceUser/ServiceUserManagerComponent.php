<?php
namespace App\Livewire\ServiceUser;

use App\Models\ServiceUser;
use App\Models\User;
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

    // Batch 15: key care information — kept on the profile itself rather
    // than only inside a care plan, so it's there even before one exists.
    public string $formAllergies             = '';
    public string $formMedicalConditions     = '';
    public string $formCommunicationNeeds    = '';
    public string $formPreferences           = '';
    public string $formImportantInformation  = '';

    // Batch 16: the named person responsible for this service user's
    // alerts — see App\Support\AlertGenerator.
    public ?int $formCareCoordinatorId = null;

    public array $coordinatorOptions = [];

    // Batch 21: who pays for this person's care and how the charge is
    // worked out — see InvoiceRun::generateInvoices().
    public string $formBillingType = 'per_visit';
    public ?float $formBillingRate = null;
    public ?float $formBillingPackageAmount = null;
    public string $formBillingPackagePeriod = 'weekly';
    public string $formPayerType = 'self_funded';
    public string $formPayerName = '';
    public string $formPayerAddress = '';
    public string $formPayerEmail = '';
    public string $formLocalAuthorityName = '';
    public string $formLocalAuthorityReference = '';
    public ?float $formLocalAuthorityShareAmount = null;

    // Batch 35: geofenced check-in restriction — see
    // App\Models\ServiceUser::distanceInMetersTo() and
    // App\Livewire\Rota\ShiftVisitComponent::checkIn(). Location is
    // auto-geocoded from the address by default; formManualLocation
    // switches to a manually-pinned lat/long instead.
    public bool $formManualLocation = false;
    public ?float $formLatitude = null;
    public ?float $formLongitude = null;

    public function mount(): void
    {
        // abort_unless(Auth::user()->hasAnyRole(['admin', 'manager']), 403);

        $this->coordinatorOptions = User::where('agency_id', Auth::user()->agency_id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Manager', 'Admin', 'Super Admin']))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
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
        $this->formAllergies            = $su->allergies ?? '';
        $this->formMedicalConditions    = $su->medical_conditions ?? '';
        $this->formCommunicationNeeds   = $su->communication_needs ?? '';
        $this->formPreferences          = $su->preferences ?? '';
        $this->formImportantInformation = $su->important_information ?? '';
        $this->formCareCoordinatorId    = $su->care_coordinator_id;
        $this->formBillingType              = $su->billing_type ?? 'per_visit';
        $this->formBillingRate              = $su->billing_rate !== null ? (float) $su->billing_rate : null;
        $this->formBillingPackageAmount      = $su->billing_package_amount !== null ? (float) $su->billing_package_amount : null;
        $this->formBillingPackagePeriod      = $su->billing_package_period ?? 'weekly';
        $this->formPayerType                 = $su->payer_type ?? 'self_funded';
        $this->formPayerName                 = $su->payer_name ?? '';
        $this->formPayerAddress              = $su->payer_address ?? '';
        $this->formPayerEmail                = $su->payer_email ?? '';
        $this->formLocalAuthorityName         = $su->local_authority_name ?? '';
        $this->formLocalAuthorityReference    = $su->local_authority_reference ?? '';
        $this->formLocalAuthorityShareAmount  = $su->local_authority_share_amount !== null ? (float) $su->local_authority_share_amount : null;
        $this->formManualLocation = $su->location_source === 'manual';
        $this->formLatitude       = $su->latitude !== null ? (float) $su->latitude : null;
        $this->formLongitude      = $su->longitude !== null ? (float) $su->longitude : null;
        $this->dispatch('open-drawer', 'service-user-form');
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingServiceUserId', 'formName', 'formDob', 'formGender', 'formAddress',
            'formNhsNumber', 'formNextOfKinName', 'formNextOfKinContact', 'formConsentStatus',
            'formAllergies', 'formMedicalConditions', 'formCommunicationNeeds',
            'formPreferences', 'formImportantInformation', 'formCareCoordinatorId',
            'formBillingType', 'formBillingRate', 'formBillingPackageAmount', 'formBillingPackagePeriod',
            'formPayerType', 'formPayerName', 'formPayerAddress', 'formPayerEmail',
            'formLocalAuthorityName', 'formLocalAuthorityReference', 'formLocalAuthorityShareAmount',
            'formManualLocation', 'formLatitude', 'formLongitude',
        ]);
        $this->formBillingType = 'per_visit';
        $this->formBillingPackagePeriod = 'weekly';
        $this->formPayerType = 'self_funded';
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
            'formAllergies'            => 'nullable|string|max:2000',
            'formMedicalConditions'    => 'nullable|string|max:2000',
            'formCommunicationNeeds'   => 'nullable|string|max:2000',
            'formPreferences'          => 'nullable|string|max:2000',
            'formImportantInformation' => 'nullable|string|max:2000',
            'formCareCoordinatorId'    => 'nullable|exists:users,id',
            'formBillingType'               => 'required|in:per_visit,flat_package',
            'formBillingRate'               => 'nullable|numeric|min:0',
            'formBillingPackageAmount'      => 'nullable|numeric|min:0',
            'formBillingPackagePeriod'      => 'nullable|in:weekly,monthly',
            'formPayerType'                 => 'required|in:self_funded,local_authority,split',
            'formPayerName'                 => 'nullable|string|max:255',
            'formPayerAddress'              => 'nullable|string',
            'formPayerEmail'                => 'nullable|email|max:255',
            'formLocalAuthorityName'        => 'nullable|string|max:255',
            'formLocalAuthorityReference'   => 'nullable|string|max:255',
            'formLocalAuthorityShareAmount' => 'nullable|numeric|min:0',
            'formLatitude'  => $this->formManualLocation ? 'required|numeric|between:-90,90' : 'nullable|numeric|between:-90,90',
            'formLongitude' => $this->formManualLocation ? 'required|numeric|between:-180,180' : 'nullable|numeric|between:-180,180',
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
            'allergies'               => $this->formAllergies ?: null,
            'medical_conditions'      => $this->formMedicalConditions ?: null,
            'communication_needs'     => $this->formCommunicationNeeds ?: null,
            'preferences'             => $this->formPreferences ?: null,
            'important_information'   => $this->formImportantInformation ?: null,
            'care_coordinator_id'     => $this->formCareCoordinatorId ?: null,
            'billing_type'                  => $this->formBillingType,
            'billing_rate'                  => $this->formBillingRate ?: null,
            'billing_package_amount'        => $this->formBillingPackageAmount ?: null,
            'billing_package_period'        => $this->formBillingPackagePeriod ?: null,
            'payer_type'                    => $this->formPayerType,
            'payer_name'                    => $this->formPayerName ?: null,
            'payer_address'                 => $this->formPayerAddress ?: null,
            'payer_email'                   => $this->formPayerEmail ?: null,
            'local_authority_name'          => $this->formLocalAuthorityName ?: null,
            'local_authority_reference'     => $this->formLocalAuthorityReference ?: null,
            'local_authority_share_amount'  => $this->formLocalAuthorityShareAmount ?: null,
        ];

        // Batch 20: create/update are now logged automatically by
        // ServiceUser's LogsActivity trait — replaces the old
        // AuditLogger::log('SERVICE_USER_CREATED'/'_UPDATED', ...) calls.
        if ($this->editingServiceUserId) {
            $su = ServiceUser::findOrFail($this->editingServiceUserId);
            $su->update($data);
        } else {
            $su = ServiceUser::create($data);
        }

        $this->applyLocation($su);

        $this->resetForm();
        $this->dispatch('close-drawer', 'service-user-form');
        $this->dispatch('toast', message: 'Service user saved.', type: 'success');
    }

    /**
     * Batch 35: sets this service user's geofence location either to the
     * manager's exact manual pin, or — the default — auto-geocoded from
     * the address whenever ServiceUser::needsGeocoding() says it's missing
     * or stale. A failed geocode (bad address, network hiccup) is silent
     * by design: the geofence simply isn't enforced for this person until
     * an address/pin resolves, exactly as if this feature didn't exist.
     */
    protected function applyLocation(ServiceUser $su): void
    {
        if ($this->formManualLocation && $this->formLatitude !== null && $this->formLongitude !== null) {
            $su->forceFill([
                'latitude' => $this->formLatitude,
                'longitude' => $this->formLongitude,
                'location_source' => 'manual',
                'geocoded_address' => null,
            ])->save();

            return;
        }

        if ($su->location_source === 'manual') {
            $su->forceFill(['location_source' => null])->save();
        }

        if ($su->needsGeocoding()) {
            $su->geocodeAddress();
        }
    }

    public function toggleActive(string $serviceUserId): void
    {
        $su = ServiceUser::withTrashed()->findOrFail($serviceUserId);

        // Batch 20: restore/delete are now logged automatically (as
        // "restored"/"deleted" events) by ServiceUser's LogsActivity trait,
        // which detects the SoftDeletes trait — replaces the old
        // AuditLogger::log('SERVICE_USER_REACTIVATED'/'_DEACTIVATED', ...)
        // calls.
        if ($su->trashed()) {
            $su->restore();
            $this->dispatch('toast', message: 'Service user reactivated.', type: 'success');
        } else {
            $su->delete();
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
