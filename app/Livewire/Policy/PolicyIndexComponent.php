<?php

namespace App\Livewire\Policy;

use App\Models\MediaFile;
use App\Models\Policy;
use App\Models\PolicyAcknowledgment;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Livewire\Component;
use Livewire\WithFileUploads;

class PolicyIndexComponent extends Component
{
    use WithFileUploads;

    public string $statusFilter = 'active';

    // Create/edit form (drawer) state
    public ?string $editingPolicyId = null;
    public string $formTitle = '';
    public string $formCategory = 'other';
    public string $formDescription = '';
    public string $formVersion = '1.0';
    public string $formEffectiveDate = '';
    public string $formReviewDate = '';
    public bool $formIsMandatoryReading = false;
    public bool $formIsActive = true;
    public $formDocument = null;

    public function canManage(): bool
    {
        $user = Auth::user();
        return $user->can('policy.manage') || $user->hasRole(['Admin', 'Super Admin']);
    }

    protected function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only a manager can manage policies.');
    }

    public function openCreateForm(): void
    {
        $this->authorizeManage();
        $this->resetForm();
        $this->dispatch('open-drawer', 'policy-form');
    }

    public function openEditForm(string $policyId): void
    {
        $this->authorizeManage();
        $policy = Policy::findOrFail($policyId);

        $this->editingPolicyId = $policy->id;
        $this->formTitle = $policy->title;
        $this->formCategory = $policy->category;
        $this->formDescription = $policy->description ?? '';
        $this->formVersion = $policy->version;
        $this->formEffectiveDate = $policy->effective_date?->toDateString() ?? '';
        $this->formReviewDate = $policy->review_date?->toDateString() ?? '';
        $this->formIsMandatoryReading = $policy->is_mandatory_reading;
        $this->formIsActive = $policy->is_active;
        $this->dispatch('open-drawer', 'policy-form');
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingPolicyId', 'formTitle', 'formDescription', 'formEffectiveDate',
            'formReviewDate', 'formDocument',
        ]);
        $this->formCategory = 'other';
        $this->formVersion = '1.0';
        $this->formIsMandatoryReading = false;
        $this->formIsActive = true;
    }

    public function savePolicy(): void
    {
        $this->authorizeManage();

        $this->validate([
            'formTitle' => 'required|string|max:255',
            'formCategory' => 'required|string|max:255',
            'formVersion' => 'required|string|max:50',
            'formEffectiveDate' => 'required|date',
            'formReviewDate' => 'required|date|after_or_equal:formEffectiveDate',
            'formDocument' => 'nullable|file|max:10240',
        ]);

        $documentId = null;
        if ($this->formDocument) {
            $path = $this->formDocument->store('policy-documents', 'public');
            $documentId = MediaFile::create([
                'file_name' => $this->formDocument->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $this->formDocument->getMimeType(),
                'file_size' => $this->formDocument->getSize(),
                'uploaded_by' => Auth::id(),
            ])->id;
        }

        $data = [
            'agency_id' => Auth::user()->agency_id,
            'title' => $this->formTitle,
            'category' => $this->formCategory,
            'description' => $this->formDescription ?: null,
            'version' => $this->formVersion,
            'effective_date' => $this->formEffectiveDate,
            'review_date' => $this->formReviewDate,
            'is_mandatory_reading' => $this->formIsMandatoryReading,
            'is_active' => $this->formIsActive,
            'updated_by' => Auth::id(),
        ];

        if ($documentId) {
            $data['document_id'] = $documentId;
        }

        if ($this->editingPolicyId) {
            $policy = Policy::findOrFail($this->editingPolicyId);
            $policy->update($data);
            AuditLogger::log('POLICY_UPDATED', $policy);
        } else {
            $data['created_by'] = Auth::id();
            $policy = Policy::create($data);
            AuditLogger::log('POLICY_PUBLISHED', $policy);
        }

        $this->resetForm();
        $this->dispatch('close-drawer', 'policy-form');
        $this->dispatch('toast', message: 'Policy saved.', type: 'success');
    }

    public function acknowledge(string $policyId): void
    {
        $policy = Policy::findOrFail($policyId);

        if ($policy->isAcknowledgedBy(Auth::id())) {
            return;
        }

        PolicyAcknowledgment::create([
            'policy_id' => $policy->id,
            'user_id' => Auth::id(),
            'acknowledged_at' => now(),
            'ip_address' => Request::ip(),
            'created_by' => Auth::id(),
        ]);

        AuditLogger::log('POLICY_ACKNOWLEDGED', $policy);

        $this->dispatch('toast', message: 'Policy acknowledged.', type: 'success');
    }

    public function render()
    {
        $agencyId = Auth::user()->agency_id;
        $userId = Auth::id();

        $policies = Policy::where(function ($q) use ($agencyId) {
                $q->where('agency_id', $agencyId)->orWhereNull('agency_id');
            })
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->withCount('acknowledgments')
            ->orderByDesc('effective_date')
            ->get()
            ->map(function (Policy $policy) use ($userId) {
                $policy->setAttribute('acknowledged_by_me', $policy->isAcknowledgedBy($userId));
                return $policy;
            });

        return view('livewire.policy.policy-index', [
            'policies' => $policies,
            'canManage' => $this->canManage(),
        ]);
    }
}
