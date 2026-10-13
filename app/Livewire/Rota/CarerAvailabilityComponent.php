<?php
namespace App\Livewire\Rota;

use App\Models\CarerUnavailability;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Batch 18: a simple date-range "don't schedule me" log per carer — see
 * App\Models\CarerUnavailability. App\Livewire\Rota\RotaBuilder checks
 * against this when a carer is assigned a shift and warns (doesn't
 * block) if the date falls inside one of their own windows here.
 */
class CarerAvailabilityComponent extends Component
{
    use WithPagination;

    public ?string $editingId   = null;
    public string $formCarerId  = '';
    public string $formStartDate = '';
    public string $formEndDate   = '';
    public string $formReason    = '';

    public array $carers = [];

    public function mount(): void
    {
        abort_unless(Auth::user()->can('manage_rota') || Auth::user()->hasRole(['Admin', 'Super Admin']), 403);

        $this->carers = User::where('agency_id', Auth::user()->agency_id)->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->dispatch('open-drawer', 'availability-form');
    }

    public function openEditForm(string $id): void
    {
        $record = CarerUnavailability::findOrFail($id);

        $this->editingId     = $record->id;
        $this->formCarerId   = (string) $record->user_id;
        $this->formStartDate = $record->start_date->toDateString();
        $this->formEndDate   = $record->end_date->toDateString();
        $this->formReason    = $record->reason ?? '';
        $this->dispatch('open-drawer', 'availability-form');
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'formCarerId', 'formStartDate', 'formEndDate', 'formReason']);
    }

    public function save(): void
    {
        $this->validate([
            'formCarerId'   => 'required',
            'formStartDate' => 'required|date',
            'formEndDate'   => 'required|date|after_or_equal:formStartDate',
            'formReason'    => 'nullable|string|max:255',
        ]);

        $data = [
            'agency_id'  => Auth::user()->agency_id,
            'user_id'    => $this->formCarerId,
            'start_date' => $this->formStartDate,
            'end_date'   => $this->formEndDate,
            'reason'     => $this->formReason ?: null,
            'updated_by' => Auth::id(),
        ];

        if ($this->editingId) {
            CarerUnavailability::whereKey($this->editingId)->update($data);
        } else {
            $data['created_by'] = Auth::id();
            CarerUnavailability::create($data);
        }

        $this->resetForm();
        $this->dispatch('close-drawer', 'availability-form');
        $this->dispatch('toast', message: 'Unavailability saved.', type: 'success');
    }

    public function delete(string $id): void
    {
        CarerUnavailability::whereKey($id)->delete();
        $this->dispatch('toast', message: 'Unavailability removed.', type: 'warning');
    }

    public function render()
    {
        $records = CarerUnavailability::where('agency_id', Auth::user()->agency_id)
            ->with('user')
            ->orderByDesc('start_date')
            ->paginate(12);

        return view('livewire.rota.carer-availability', compact('records'));
    }
}
