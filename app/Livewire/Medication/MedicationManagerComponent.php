<?php

namespace App\Livewire\Medication;

use App\Models\Medication;
use App\Models\ServiceUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MedicationManagerComponent extends Component
{
    public string $serviceUserId;

    public bool $showInactive = false;

    // Medication form (drawer) state
    public ?string $editingMedicationId = null;
    public string $formMedicationName = '';
    public string $formDosage = '';
    public string $formFrequency = '';
    public string $formAdministrationRoute = 'oral';
    public string $formScheduledTimes = '';
    public string $formStartDate = '';
    public string $formEndDate = '';
    public bool $formIsPrn = false;
    public string $formInstructions = '';
    public string $formSideEffects = '';

    public function mount(string $serviceUserId): void
    {
        $this->serviceUserId = $serviceUserId;
        $this->formStartDate = now()->toDateString();
    }

    protected function serviceUser(): ServiceUser
    {
        return ServiceUser::findOrFail($this->serviceUserId);
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->dispatch('open-drawer', 'medication-form');
    }

    public function openEditForm(string $medicationId): void
    {
        $med = Medication::findOrFail($medicationId);

        $this->editingMedicationId = $med->id;
        $this->formMedicationName = $med->medication_name;
        $this->formDosage = $med->dosage;
        $this->formFrequency = $med->frequency;
        $this->formAdministrationRoute = $med->administration_route;
        $this->formScheduledTimes = $med->scheduled_times ? substr($med->scheduled_times, 0, 5) : '';
        $this->formStartDate = $med->start_date->toDateString();
        $this->formEndDate = $med->end_date?->toDateString() ?? '';
        $this->formIsPrn = $med->is_prn;
        $this->formInstructions = $med->instructions ?? '';
        $this->formSideEffects = $med->side_effects ?? '';
        $this->dispatch('open-drawer', 'medication-form');
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingMedicationId', 'formMedicationName', 'formDosage', 'formFrequency',
            'formScheduledTimes', 'formEndDate', 'formIsPrn', 'formInstructions', 'formSideEffects',
        ]);
        $this->formAdministrationRoute = 'oral';
        $this->formStartDate = now()->toDateString();
    }

    public function saveMedication(): void
    {
        $this->validate([
            'formMedicationName' => 'required|string|max:255',
            'formDosage' => 'required|string|max:255',
            'formFrequency' => 'required|string|max:255',
            'formAdministrationRoute' => 'required|string',
            'formScheduledTimes' => $this->formIsPrn ? 'nullable' : 'required',
            'formStartDate' => 'required|date',
            'formEndDate' => 'nullable|date|after_or_equal:formStartDate',
        ]);

        $data = [
            'service_user_id' => $this->serviceUserId,
            'medication_name' => $this->formMedicationName,
            'dosage' => $this->formDosage,
            'frequency' => $this->formFrequency,
            'administration_route' => $this->formAdministrationRoute,
            'scheduled_times' => $this->formIsPrn ? null : $this->formScheduledTimes,
            'start_date' => $this->formStartDate,
            'end_date' => $this->formEndDate ?: null,
            'is_prn' => $this->formIsPrn,
            'instructions' => $this->formInstructions ?: null,
            'side_effects' => $this->formSideEffects ?: null,
            'is_active' => true,
        ];

        Medication::updateOrCreate(['id' => $this->editingMedicationId], $data);

        $this->resetForm();
        $this->dispatch('close-drawer', 'medication-form');
        $this->dispatch('toast', message: 'Medication saved.', type: 'success');
    }

    public function toggleActive(string $medicationId): void
    {
        $med = Medication::findOrFail($medicationId);
        $med->update(['is_active' => ! $med->is_active]);
        $this->dispatch('toast', message: $med->is_active ? 'Medication reactivated.' : 'Medication discontinued.', type: 'success');
    }

    public function render()
    {
        $serviceUser = $this->serviceUser();

        $medications = $serviceUser->medications()
            ->when(! $this->showInactive, fn ($q) => $q->where('is_active', true))
            ->orderByDesc('is_active')
            ->orderBy('medication_name')
            ->get();

        return view('livewire.medication.medication-manager', compact('serviceUser', 'medications'));
    }
}
