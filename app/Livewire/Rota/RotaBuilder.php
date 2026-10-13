<?php
namespace App\Livewire\Rota;

use App\Models\CarerUnavailability;
use App\Models\RecurringVisitTemplate;
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
    public array $grid         = []; // [service_user_id][date]['day'|'night'] => array of shift arrays (0, 1 or 2)
    public array $liveInByServiceUser = []; // service_user_id => ['carer_name' => ..., 'end' => 'd/m'][]

    // Shift form (drawer) state
    public ?string $editingShiftId     = null;
    // Batch 18: set instead of editingShiftId when the drawer is adding a
    // SECOND carer to an existing shift (double-handed) rather than
    // editing it — see openAddSecondCarerForm()/saveShift().
    public ?string $linkingShiftId     = null;
    public string $formServiceUserId   = '';
    public string $formServiceUserName = '';
    public string $formDate            = '';
    public string $formShiftType       = 'day';
    public string $formCarerId         = '';
    public string $formStart           = '';
    public string $formEnd             = '';
    public int $formBreakMinutes       = 0;
    public string $formNotes           = '';

    // Carers found unavailable (App\Models\CarerUnavailability) for the
    // currently-selected carer/date, surfaced as a warning once saveShift()
    // runs — not blocked, just flagged.
    public ?string $unavailableWarning = null;

    public function mount(string $rotaPeriodId): void
    {
        // Batch 4: this had no authorization check at all — any
        // authenticated staff member, including a Carer, could open the
        // rota builder and edit anyone's shifts. Flagged as a known gap in
        // CHANGES3.md; closed here.
        abort_unless(Auth::user()->can('manage_rota') || Auth::user()->hasRole(['Admin', 'Super Admin']), 403);

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
        $weekEnd   = $weekStart->copy()->addDays(6);

        $this->days = [];
        for ($i = 0; $i < 7; $i++) {
            $date                              = $weekStart->copy()->addDays($i);
            $this->days[$date->toDateString()] = $date->format('D d/m');
        }

        $this->grid = [];
        foreach ($this->serviceUsers as $suId => $suName) {
            foreach (array_keys($this->days) as $date) {
                $this->grid[$suId][$date] = ['day' => [], 'night' => []];
            }
        }

        $shifts = $period->shifts()->with('carer')->get();

        // Batch 18: carers with an unavailability window overlapping this
        // week, and any pair of a carer's shifts this week that overlap in
        // time without being a deliberate double-handed link — both
        // computed once here rather than per-cell.
        $agencyId        = Auth::user()->agency_id;
        $unavailabilities = CarerUnavailability::where('agency_id', $agencyId)
            ->where('start_date', '<=', $weekEnd->toDateString())
            ->where('end_date', '>=', $weekStart->toDateString())
            ->get();
        $conflictingShiftIds = $this->computeConflicts($shifts);

        // Live-in placements overlapping this week, keyed by service user,
        // so the grid can flag "already covered by a live-in carer" next
        // to that person's name instead of silently allowing a day/night
        // shift to be booked on top of it.
        $this->liveInByServiceUser = Shift::where('agency_id', $agencyId)
            ->where('shift_type', Shift::TYPE_LIVE_IN)
            ->whereNotNull('assigned_to')
            ->where('scheduled_start', '<=', $weekEnd->toDateString().' 23:59:59')
            ->where('scheduled_end', '>=', $weekStart->toDateString().' 00:00:00')
            ->with('carer')
            ->get()
            ->groupBy('service_user_id')
            ->map(fn ($group) => $group->map(fn (Shift $s) => [
                'carer_name' => $s->carer->name ?? __('Unassigned'),
                'end' => $s->scheduled_end->format('d/m'),
            ])->all())
            ->all();

        foreach ($shifts as $shift) {
            $date = $shift->scheduled_start->toDateString();
            $suId = $shift->service_user_id;
            $slot = $shift->shift_type ?? 'day';

            if (! isset($this->grid[$suId][$date])) {
                continue; // service user no longer active for this agency — skip defensively
            }

            $isUnavailable = $shift->assigned_to && $unavailabilities->contains(
                fn (CarerUnavailability $u) => $u->user_id == $shift->assigned_to && $u->coversDate($date)
            );

            $this->grid[$suId][$date][$slot][] = [
                'id'            => $shift->id,
                'carer_name'    => $shift->carer->name ?? __('Unassigned'),
                'start'         => $shift->scheduled_start->format('H:i'),
                'end'           => $shift->scheduled_end->format('H:i'),
                'break_minutes' => $shift->break_minutes,
                'status'        => $shift->status,
                'is_double_handed' => $shift->linked_shift_id !== null,
                'is_unavailable'    => $isUnavailable,
                'is_conflicting'    => in_array($shift->id, $conflictingShiftIds, true),
            ];
        }
    }

    /**
     * Any two of the same carer's shifts this week that overlap in time
     * without being a deliberate double-handed pair (linked_shift_id).
     * Scoped to this rota period only — a conflict spanning two different
     * weeks, or against a CareCall rather than a Shift, isn't caught here;
     * see CHANGES18.md.
     */
    protected function computeConflicts(\Illuminate\Support\Collection $shifts): array
    {
        $conflicting = [];

        foreach ($shifts->whereNotNull('assigned_to')->groupBy('assigned_to') as $carerShifts) {
            $list = $carerShifts->values();

            for ($i = 0; $i < $list->count(); $i++) {
                for ($j = $i + 1; $j < $list->count(); $j++) {
                    $a = $list[$i];
                    $b = $list[$j];

                    if ($a->linked_shift_id === $b->id || $b->linked_shift_id === $a->id) {
                        continue; // intentional double-handed pairing
                    }

                    if ($a->scheduled_start->lessThan($b->scheduled_end) && $b->scheduled_start->lessThan($a->scheduled_end)) {
                        $conflicting[$a->id] = true;
                        $conflicting[$b->id] = true;
                    }
                }
            }
        }

        return array_keys($conflicting);
    }

    public function openCreateForm(string $serviceUserId, string $date, string $shiftType): void
    {
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

    /**
     * Batch 18: the double-handed flow — pre-fills everything from the
     * existing shift (service user, date, slot, times) except the carer,
     * which the manager picks fresh. saveShift() sees linkingShiftId set
     * and creates a new, separate Shift row linked to this one, rather
     * than editing it — each carer keeps their own row (and their own
     * timesheet attribution) exactly as a single-handed visit would.
     */
    public function openAddSecondCarerForm(string $shiftId): void
    {
        $shift = Shift::findOrFail($shiftId);

        $this->resetForm();
        $this->linkingShiftId      = $shift->id;
        $this->formServiceUserId   = $shift->service_user_id;
        $this->formServiceUserName = $this->serviceUsers[$shift->service_user_id] ?? '';
        $this->formDate            = $shift->scheduled_start->toDateString();
        $this->formShiftType       = $shift->shift_type ?? 'day';
        $this->formStart           = $shift->scheduled_start->format('H:i');
        $this->formEnd             = $shift->scheduled_end->format('H:i');
        $this->formBreakMinutes    = $shift->break_minutes ?? 0;
        $this->dispatch('open-drawer', 'shift-form');
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingShiftId', 'linkingShiftId', 'formCarerId', 'formStart', 'formEnd', 'formNotes',
            'unavailableWarning',
        ]);
        $this->formBreakMinutes = 0;
    }

    /**
     * Handles create, edit, and "add a second carer" (double-handed) —
     * day/night shift assignment for one service user on one date. A
     * shift crossing midnight (e.g. a night shift 20:00-08:00) is
     * detected and rolled to the next day automatically.
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
        } elseif ($this->linkingShiftId) {
            $data['created_by'] = Auth::id();
            $second = Shift::create($data);
            Shift::findOrFail($this->linkingShiftId)->linkWith($second);
        } else {
            $data['created_by'] = Auth::id();
            Shift::create($data);
        }

        $warning = $this->carerUnavailableWarning((int) $this->formCarerId, $this->formDate);

        $this->resetForm();
        $this->dispatch('close-drawer', 'shift-form');
        $this->dispatch('toast', message: $warning ?: 'Shift saved.', type: $warning ? 'warning' : 'success');
        $this->loadGrid();
    }

    protected function carerUnavailableWarning(int $carerId, string $date): ?string
    {
        $unavailable = CarerUnavailability::where('user_id', $carerId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if (! $unavailable) {
            return null;
        }

        $name = $this->carers[$carerId] ?? __('This carer');

        return __(':name is marked unavailable on this date (:reason) — shift saved anyway.', [
            'name' => $name,
            'reason' => $unavailable->reason ?: __('no reason given'),
        ]);
    }

    public function deleteShift(string $shiftId): void
    {
        Shift::whereKey($shiftId)->delete();
        $this->dispatch('toast', message: 'Shift removed.', type: 'warning');
        $this->loadGrid();
    }

    /**
     * Notifying scheduled carers happens inside RotaPeriod::publish() itself
     * (also called from RotaPeriodIndex), so both entry points behave the
     * same and the notification logic lives in one place.
     */
    public function publish(): void
    {
        $this->period()->publish();
        $this->dispatch('toast', message: 'Rota published.', type: 'success');
    }

    public function generateTimesheets(): void
    {
        $this->period()->generateTimesheets();
        $this->dispatch('toast', message: 'Timesheets generated for every scheduled carer.', type: 'success');
    }

    /**
     * Batch 18: runs every active shift-type RecurringVisitTemplate for
     * this agency across this period's week — idempotent, so running it
     * more than once (or after manually editing some of the generated
     * shifts) only fills in whatever's still missing.
     */
    public function generateFromTemplates(): void
    {
        $period = $this->period();
        $weekStart = Carbon::parse($period->week_commencing);
        $weekEnd = $weekStart->copy()->addDays(6);

        $templates = RecurringVisitTemplate::where('agency_id', $period->agency_id)
            ->where('template_type', RecurringVisitTemplate::TYPE_SHIFT)
            ->where('is_active', true)
            ->get();

        $created = 0;
        foreach ($templates as $template) {
            $created += $template->generate($weekStart, $weekEnd);
        }

        $this->dispatch('toast', message: $created > 0
            ? __(':n shift(s) generated from recurring templates.', ['n' => $created])
            : __('No new shifts to generate — everything this week is already covered.'), type: 'success');
        $this->loadGrid();
    }

    public function render()
    {
        return view('livewire.rota.rota-builder', ['period' => $this->period()]);
    }
}
