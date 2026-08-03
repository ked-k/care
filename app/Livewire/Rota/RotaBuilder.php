<?php
namespace App\Livewire\Rota;

use App\Models\RotaPeriod;
use App\Models\ServiceUser;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RotaBuilder extends Component
{
    public string $rotaPeriodId;

    public array $days         = []; // date (Y-m-d) => display label
    public array $serviceUsers = []; // id => name
    public array $carers       = []; // id => name
    public array $grid         = []; // [service_user_id][date]['day'|'night'] => shift array|null

    // Shift form (drawer) state
    public ?string $editingShiftId     = null;
    public string $formServiceUserId   = '';
    public string $formServiceUserName = '';
    public string $formDate            = '';
    public string $formShiftType       = 'day';
    public string $formCarerId         = '';
    public string $formStart           = '';
    public string $formEnd             = '';
    public int $formBreakMinutes       = 0;
    public string $formNotes           = '';

    public function mount(string $rotaPeriodId): void
    {
        $this->rotaPeriodId = $rotaPeriodId;
        $this->loadOptions();
        $this->loadGrid();
    }

    protected function period(): RotaPeriod
    {
        return RotaPeriod::findOrFail($this->rotaPeriodId);
    }

    protected function loadOptions(): void
    {
        $agencyId = Auth::user()->agency_id;

        $this->serviceUsers = ServiceUser::where('agency_id', $agencyId)
            ->orderBy('name')->pluck('name', 'id')->toArray();

        $this->carers = User::where('agency_id', $agencyId)
            ->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function loadGrid(): void
    {
        $period    = $this->period();
        $weekStart = Carbon::parse($period->week_commencing);

        $this->days = [];
        for ($i = 0; $i < 7; $i++) {
            $date                              = $weekStart->copy()->addDays($i);
            $this->days[$date->toDateString()] = $date->format('D d/m');
        }

        $this->grid = [];
        foreach ($this->serviceUsers as $suId => $suName) {
            foreach (array_keys($this->days) as $date) {
                $this->grid[$suId][$date] = ['day' => null, 'night' => null];
            }
        }

        $shifts = $period->shifts()->with('carer')->get();

        foreach ($shifts as $shift) {
            $date = $shift->scheduled_start->toDateString();
            $suId = $shift->service_user_id;
            $slot = $shift->shift_type ?? 'day';

            if (! isset($this->grid[$suId][$date])) {
                continue; // service user no longer active for this agency — skip defensively
            }

            $this->grid[$suId][$date][$slot] = [
                'id'            => $shift->id,
                'carer_name'    => $shift->carer->name ?? 'Unassigned',
                'start'         => $shift->scheduled_start->format('H:i'),
                'end'           => $shift->scheduled_end->format('H:i'),
                'break_minutes' => $shift->break_minutes,
                'status'        => $shift->status,
            ];
        }
    }

    public function openCreateForm(string $serviceUserId, string $date, string $shiftType): void
    {
        // dd('wwwww');
        $this->resetForm();
        $this->formServiceUserId   = $serviceUserId;
        $this->formServiceUserName = $this->serviceUsers[$serviceUserId] ?? '';
        $this->formDate            = $date;
        $this->formShiftType       = $shiftType;
        $this->dispatch('open-drawer', 'shift-form');
    }

    public function openEditForm(string $shiftId): void
    {

        $shift = Shift::findOrFail($shiftId);

        $this->editingShiftId      = $shift->id;
        $this->formServiceUserId   = $shift->service_user_id;
        $this->formServiceUserName = $this->serviceUsers[$shift->service_user_id] ?? '';
        $this->formDate            = $shift->scheduled_start->toDateString();
        $this->formShiftType       = $shift->shift_type ?? 'day';
        $this->formCarerId         = (string) $shift->assigned_to;
        $this->formStart           = $shift->scheduled_start->format('H:i');
        $this->formEnd             = $shift->scheduled_end->format('H:i');
        $this->formBreakMinutes    = $shift->break_minutes ?? 0;
        $this->formNotes           = $shift->notes ?? '';
        $this->dispatch('open-drawer', 'shift-form');
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingShiftId', 'formCarerId', 'formStart', 'formEnd', 'formNotes',
        ]);
        $this->formBreakMinutes = 0;
    }

    /**
     * Handles both create and edit — day/night shift assignment for one
     * service user on one date. A shift crossing midnight (e.g. a night
     * shift 20:00-08:00) is detected and rolled to the next day automatically.
     */
    public function saveShift(): void
    {
        $this->validate([
            'formServiceUserId' => 'required',
            'formDate'          => 'required|date',
            'formShiftType'     => 'required|in:day,night',
            'formCarerId'       => 'required',
            'formStart'         => 'required',
            'formEnd'           => 'required',
            'formBreakMinutes'  => 'nullable|integer|min:0',
        ]);

        $start = Carbon::parse("{$this->formDate} {$this->formStart}");
        $end   = Carbon::parse("{$this->formDate} {$this->formEnd}");
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $period = $this->period();

        $data = [
            'agency_id'       => $period->agency_id,
            'rota_period_id'  => $period->id,
            'service_user_id' => $this->formServiceUserId,
            'assigned_to'     => $this->formCarerId,
            'shift_type'      => $this->formShiftType,
            'scheduled_start' => $start,
            'scheduled_end'   => $end,
            'break_minutes'   => $this->formBreakMinutes ?: 0,
            'status'          => 'scheduled',
            'notes'           => $this->formNotes ?: null,
            'updated_by'      => Auth::id(),
        ];

        if ($this->editingShiftId) {
            Shift::whereKey($this->editingShiftId)->update($data);
        } else {
            $data['created_by'] = Auth::id();
            Shift::create($data);
        }

        $this->resetForm();
        $this->dispatch('close-drawer', 'shift-form');
        $this->dispatch('toast', message: 'Shift saved.', type: 'success');
        $this->loadGrid();
    }

    public function deleteShift(string $shiftId): void
    {
        Shift::whereKey($shiftId)->delete();
        $this->dispatch('toast', message: 'Shift removed.', type: 'warning');
        $this->loadGrid();
    }

    public function publish(): void
    {
        $this->period()->update(['status' => 'published']);
        $this->dispatch('toast', message: 'Rota published.', type: 'success');
    }

    public function generateTimesheets(): void
    {
        $this->period()->generateTimesheets();
        $this->dispatch('toast', message: 'Timesheets generated for every scheduled carer.', type: 'success');
    }

    public function render()
    {
        return view('livewire.rota.rota-builder', ['period' => $this->period()]);
    }
}
