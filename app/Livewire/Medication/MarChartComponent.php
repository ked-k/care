<?php

namespace App\Livewire\Medication;

use App\Models\MediaFile;
use App\Models\Medication;
use App\Models\MedicationAdministration;
use App\Models\ServiceUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class MarChartComponent extends Component
{
    use WithFileUploads;

    public string $serviceUserId;

    public string $weekStart; // Monday of the viewed week (Y-m-d)

    public array $days = [];
    public array $scheduledMeds = [];
    public array $prnMeds = [];

    // grid[medicationId][date] => ['state' => ..., 'administration' => [...]|null]
    public array $grid = [];
    public array $prnLogsThisWeek = [];

    // Recording drawer state
    public ?string $recordingMedicationId = null;
    public ?string $recordingDate = null; // for scheduled meds only
    public string $recordStatus = 'given';
    public string $recordActualTime = '';
    public string $recordNotes = '';
    public string $recordRefusalReason = '';
    public string $recordWitness = '';
    public $recordPhoto = null;

    // View-only drawer state (already recorded)
    public ?array $viewingAdministration = null;

    public function mount(string $serviceUserId, ?string $week = null): void
    {
        $this->serviceUserId = $serviceUserId;
        $this->weekStart = $week
            ? Carbon::parse($week)->startOfWeek()->toDateString()
            : now()->startOfWeek()->toDateString();

        $this->loadChart();
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
        $this->loadChart();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->toDateString();
        $this->loadChart();
    }

    public function loadChart(): void
    {
        $weekStart = Carbon::parse($this->weekStart);
        $weekEnd = $weekStart->copy()->endOfWeek();

        $this->days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $this->days[$date->toDateString()] = $date->format('D d/m');
        }

        $serviceUser = ServiceUser::findOrFail($this->serviceUserId);

        $allMeds = $serviceUser->medications()->where('is_active', true)->orderBy('medication_name')->get();
        $this->scheduledMeds = $allMeds->where('is_prn', false)->values()
            ->map(fn (Medication $m) => ['id' => $m->id, 'name' => $m->medication_name, 'dosage' => $m->dosage, 'route' => $m->administration_route, 'time' => $m->scheduledTimeFormatted()])
            ->toArray();
        $this->prnMeds = $allMeds->where('is_prn', true)->values()
            ->map(fn (Medication $m) => ['id' => $m->id, 'name' => $m->medication_name, 'dosage' => $m->dosage, 'route' => $m->administration_route])
            ->toArray();

        $medIds = $allMeds->pluck('id');
        $administrations = MedicationAdministration::whereIn('medication_id', $medIds)
            ->whereBetween('scheduled_time', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
            ->with('administeredBy')
            ->get();

        $adminByMedAndDate = $administrations->groupBy(fn ($a) => $a->medication_id.'|'.$a->scheduled_time->toDateString());

        $this->grid = [];
        foreach ($allMeds->where('is_prn', false) as $med) {
            foreach (array_keys($this->days) as $date) {
                $key = $med->id.'|'.$date;
                $admin = $adminByMedAndDate->get($key)?->first();

                if ($admin) {
                    $state = $admin->status;
                } elseif (! $med->isActiveOn(Carbon::parse($date))) {
                    $state = 'n/a';
                } elseif (Carbon::parse($date.' '.$med->scheduled_times)->isPast()) {
                    $state = 'overdue';
                } else {
                    $state = 'upcoming';
                }

                $this->grid[$med->id][$date] = [
                    'state' => $state,
                    'administration' => $admin ? $this->serializeAdministration($admin) : null,
                ];
            }
        }

        $this->prnLogsThisWeek = $administrations
            ->whereIn('medication_id', $allMeds->where('is_prn', true)->pluck('id'))
            ->map(fn ($a) => $this->serializeAdministration($a))
            ->values()
            ->toArray();
    }

    protected function serializeAdministration(MedicationAdministration $a): array
    {
        return [
            'id' => $a->id,
            'status' => $a->status,
            'scheduled_time' => $a->scheduled_time->format('d M, H:i'),
            'actual_time' => $a->actual_time?->format('d M, H:i'),
            'administered_by' => $a->administeredBy->name ?? '—',
            'notes' => $a->notes,
            'refusal_reason' => $a->refusal_reason,
            'witness_signature' => $a->witness_signature,
            'has_photo' => (bool) $a->photo_id,
            'photo_url' => $a->photo?->url(),
        ];
    }

    /**
     * Opens the recording drawer for a scheduled dose on a given date, or (when
     * $date is null) for a PRN medication logged right now.
     */
    public function openRecordForm(string $medicationId, ?string $date = null): void
    {
        $this->recordingMedicationId = $medicationId;
        $this->recordingDate = $date;
        $this->reset(['recordNotes', 'recordRefusalReason', 'recordWitness', 'recordPhoto']);
        $this->recordStatus = 'given';

        $med = Medication::findOrFail($medicationId);
        $this->recordActualTime = $date
            ? $date.'T'.($med->scheduledTimeFormatted() ?? now()->format('H:i'))
            : now()->format('Y-m-d\TH:i');

        $this->dispatch('open-drawer', 'mar-record-form');
    }

    public function viewAdministration(string $medicationId, string $date): void
    {
        $this->viewingAdministration = $this->grid[$medicationId][$date]['administration'] ?? null;
        $this->dispatch('open-drawer', 'mar-view');
    }

    public function recordAdministration(): void
    {
        $this->validate([
            'recordStatus' => 'required|in:given,prompted,refused,missed',
            'recordActualTime' => 'required|date',
            'recordRefusalReason' => $this->recordStatus === 'refused' ? 'required|string' : 'nullable|string',
            'recordPhoto' => 'nullable|image|max:5120',
        ]);

        $med = Medication::findOrFail($this->recordingMedicationId);

        $scheduledTime = $this->recordingDate
            ? Carbon::parse($this->recordingDate.' '.($med->scheduledTimeFormatted() ?? '00:00'))
            : Carbon::parse($this->recordActualTime); // PRN — no fixed schedule, use the logged time itself

        $photoId = null;
        if ($this->recordPhoto) {
            $path = $this->recordPhoto->store('medication-photos', 'public');
            $photoId = MediaFile::create([
                'file_name' => $this->recordPhoto->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $this->recordPhoto->getMimeType(),
                'file_size' => $this->recordPhoto->getSize(),
                'uploaded_by' => Auth::id(),
            ])->id;
        }

        MedicationAdministration::create([
            'medication_id' => $med->id,
            'administered_by' => Auth::id(),
            'scheduled_time' => $scheduledTime,
            'actual_time' => $this->recordStatus === 'missed' ? null : Carbon::parse($this->recordActualTime),
            'status' => $this->recordStatus,
            'refusal_reason' => $this->recordRefusalReason ?: null,
            'notes' => $this->recordNotes ?: null,
            'witness_signature' => $this->recordWitness ?: null,
            'photo_id' => $photoId,
            'created_by' => Auth::id(),
        ]);

        $this->recordingMedicationId = null;
        $this->recordingDate = null;
        $this->dispatch('close-drawer', 'mar-record-form');
        $this->dispatch('toast', message: 'Administration recorded.', type: 'success');
        $this->loadChart();
    }

    public function render()
    {
        return view('livewire.medication.mar-chart', [
            'serviceUser' => ServiceUser::findOrFail($this->serviceUserId),
        ]);
    }
}
