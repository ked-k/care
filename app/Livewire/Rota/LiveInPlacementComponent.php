<?php
namespace App\Livewire\Rota;

use App\Models\Shift;
use App\Models\ServiceUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Batch 18: live-in placements — a carer placed with one service user
 * continuously for several days, paid a flat daily rate rather than
 * hourly day/night slots. These are still ordinary App\Models\Shift
 * rows (shift_type = TYPE_LIVE_IN), but they span many days/weeks and
 * aren't tied to a single rota_period_id, so they get their own
 * standalone management page rather than living in the weekly
 * App\Livewire\Rota\RotaBuilder grid (which still shows a "Live-in"
 * badge next to a covered service user's name).
 */
class LiveInPlacementComponent extends Component
{
    use WithPagination;

    public string $search = '';

    public ?string $editingShiftId   = null;
    public string $formServiceUserId = '';
    public string $formCarerId       = '';
    public string $formStartDate     = '';
    public string $formEndDate       = '';
    public string $formNotes         = '';

    public array $serviceUsers = [];
    public array $carers       = [];

    public function mount(): void
    {
        abort_unless(Auth::user()->can('manage_rota') || Auth::user()->hasRole(['Admin', 'Super Admin']), 403);

        $agencyId = Auth::user()->agency_id;

        $this->serviceUsers = ServiceUser::where('agency_id', $agencyId)->orderBy('name')->pluck('name', 'id')->toArray();
        $this->carers       = User::where('agency_id', $agencyId)->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->dispatch('open-drawer', 'live-in-form');
    }

    public function openEditForm(string $shiftId): void
    {
        $shift = Shift::findOrFail($shiftId);

        $this->editingShiftId    = $shift->id;
        $this->formServiceUserId = $shift->service_user_id;
        $this->formCarerId       = (string) $shift->assigned_to;
        $this->formStartDate     = $shift->scheduled_start->toDateString();
        $this->formEndDate       = $shift->scheduled_end->toDateString();
        $this->formNotes         = $shift->notes ?? '';
        $this->dispatch('open-drawer', 'live-in-form');
    }

    protected function resetForm(): void
    {
        $this->reset(['editingShiftId', 'formServiceUserId', 'formCarerId', 'formStartDate', 'formEndDate', 'formNotes']);
    }

    public function savePlacement(): void
    {
        $this->validate([
            'formServiceUserId' => 'required',
            'formCarerId'       => 'required',
            'formStartDate'     => 'required|date',
            'formEndDate'       => 'required|date|after_or_equal:formStartDate',
        ]);

        $agencyId = Auth::user()->agency_id;

        $data = [
            'agency_id'       => $agencyId,
            'rota_period_id'  => null, // spans weeks — swept by date overlap, not a single period
            'service_user_id' => $this->formServiceUserId,
            'assigned_to'     => $this->formCarerId,
            'shift_type'      => Shift::TYPE_LIVE_IN,
            'scheduled_start' => Carbon::parse($this->formStartDate)->startOfDay(),
            'scheduled_end'   => Carbon::parse($this->formEndDate)->endOfDay(),
            'break_minutes'   => 0,
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
        $this->dispatch('close-drawer', 'live-in-form');
        $this->dispatch('toast', message: 'Live-in placement saved.', type: 'success');
    }

    /**
     * Ends an open-ended (or future-dated) placement as of today, rather
     * than requiring the manager to know and re-type the right end date.
     * A placement whose end date has already passed is left alone — use
     * Edit for that.
     */
    public function endNow(string $shiftId): void
    {
        $shift = Shift::findOrFail($shiftId);
        $shift->update(['scheduled_end' => now()->endOfDay()]);

        $this->dispatch('toast', message: 'Placement ended as of today.', type: 'warning');
    }

    public function deletePlacement(string $shiftId): void
    {
        Shift::whereKey($shiftId)->delete();
        $this->dispatch('toast', message: 'Placement removed.', type: 'warning');
    }

    public function render()
    {
        $agencyId = Auth::user()->agency_id;

        $placements = Shift::where('agency_id', $agencyId)
            ->where('shift_type', Shift::TYPE_LIVE_IN)
            ->with(['serviceUser', 'carer'])
            // Grouped so the OR stays scoped to the search match and doesn't
            // leak past the agency_id/shift_type filters above.
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->whereHas('serviceUser', fn ($q3) => $q3->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('carer', fn ($q3) => $q3->where('name', 'like', "%{$this->search}%"))))
            ->orderByDesc('scheduled_start')
            ->paginate(12);

        return view('livewire.rota.live-in-placement', compact('placements'));
    }
}
