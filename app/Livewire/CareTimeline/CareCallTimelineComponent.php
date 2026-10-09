<?php

namespace App\Livewire\CareTimeline;

use App\Models\CareCall;
use App\Models\Shift;
use App\Support\CareCallStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Batch 14: the Care Call Timeline — "quickly see which visit/call they
 * are currently completing". A nested, reusable component (mounted inside
 * three different parents: the carer's standalone client-profile page,
 * the shift-visit screen's Overview tab, and the manager-facing
 * CareCallManagerComponent page) rather than three copies of the same
 * merge-and-status logic.
 *
 * Merges two sources for one day, in order:
 *  - Shift rows for this service user (the one "scheduled app visit" a
 *    client might already have — unchanged, still drives payroll).
 *  - CareCall rows (the new, separate, non-payroll calls).
 * Both go through the same App\Support\CareCallStatus so Upcoming/In
 * Progress/Completed/Late/Missed means exactly the same thing for either
 * source.
 *
 * Carers get inline Check In/Check Out for a CareCall tile (a Shift tile
 * links out to the existing ShiftVisitComponent screen instead — that
 * already owns check-in/out, tasks and medications, no need to duplicate
 * it here). Managers additionally get a Cancel action on a CareCall tile.
 * Actions are only offered for *today's* timeline — a past or future date
 * is read-only.
 */
class CareCallTimelineComponent extends Component
{
    public string $serviceUserId;
    public ?string $date = null;

    public function mount(string $serviceUserId, ?string $date = null): void
    {
        $this->serviceUserId = $serviceUserId;
        $this->date = $date;

        $serviceUser = $this->serviceUser();

        if (Auth::user()->isCarerOnly()) {
            abort_unless($this->carerIsAssigned($serviceUser->id), 403, __("You're not assigned to this person."));
        }
    }

    protected function serviceUser(): \App\Models\ServiceUser
    {
        return \App\Models\ServiceUser::where('agency_id', Auth::user()->agency_id)->findOrFail($this->serviceUserId);
    }

    protected function carerIsAssigned(string $serviceUserId): bool
    {
        $carerId = Auth::id();

        return Shift::where('assigned_to', $carerId)->where('service_user_id', $serviceUserId)->exists()
            || CareCall::where('assigned_to', $carerId)->where('service_user_id', $serviceUserId)->exists();
    }

    protected function day(): Carbon
    {
        return $this->date ? Carbon::parse($this->date)->startOfDay() : today();
    }

    protected function isToday(): bool
    {
        return $this->day()->isToday();
    }

    public function checkIn(string $callId): void
    {
        abort_unless($this->isToday(), 403, __('Only today\'s calls can be checked in.'));

        $call = CareCall::where('service_user_id', $this->serviceUserId)->findOrFail($callId);
        $call->checkIn();
        $this->dispatch('toast', message: __('Checked in.'), type: 'success');
    }

    public function checkOut(string $callId): void
    {
        abort_unless($this->isToday(), 403, __('Only today\'s calls can be checked out.'));

        $call = CareCall::where('service_user_id', $this->serviceUserId)->findOrFail($callId);
        $call->checkOut();
        $this->dispatch('toast', message: __('Checked out.'), type: 'success');
    }

    public function cancelCall(string $callId): void
    {
        abort_unless(! Auth::user()->isCarerOnly(), 403, __('Only a manager can cancel a call.'));

        $call = CareCall::where('service_user_id', $this->serviceUserId)->findOrFail($callId);
        $call->update(['status' => $call->isCancelled() ? CareCall::STATUS_SCHEDULED : CareCall::STATUS_CANCELLED]);
    }

    /**
     * Time-of-day fallback label for a Shift tile — Shift has no label
     * column of its own (shift_type is day/night staffing, a different
     * concept), so this mirrors CareCall::displayLabel()'s bucketing
     * rather than adding that method to Shift.php.
     */
    protected function shiftLabel(Carbon $start): string
    {
        $hour = $start->hour;

        return match (true) {
            $hour < 11 => __('Morning'),
            $hour < 14 => __('Lunch'),
            $hour < 18 => __('Evening'),
            default => __('Bedtime'),
        };
    }

    public function render()
    {
        $day = $this->day();
        $isToday = $this->isToday();
        $isCarer = Auth::user()->isCarerOnly();

        $shifts = Shift::with('visitCheckins')
            ->where('service_user_id', $this->serviceUserId)
            ->whereDate('scheduled_start', $day)
            ->get()
            ->map(function (Shift $shift) {
                $checkin = $shift->visitCheckins->sortByDesc('checkin_time')->first();

                return [
                    'key' => 'shift-'.$shift->id,
                    'source' => 'shift',
                    'id' => $shift->id,
                    'label' => $this->shiftLabel($shift->scheduled_start),
                    'scheduled_start' => $shift->scheduled_start,
                    'scheduled_end' => $shift->scheduled_end,
                    'actual_start' => $checkin?->checkin_time,
                    'actual_end' => $checkin?->checkout_time,
                    'status' => CareCallStatus::compute(
                        $shift->scheduled_start,
                        $shift->scheduled_end,
                        $checkin?->checkin_time,
                        $checkin?->checkout_time,
                    ),
                    'visit_route' => route('rota.visit', $shift->id),
                ];
            });

        $calls = CareCall::where('service_user_id', $this->serviceUserId)
            ->whereDate('scheduled_start', $day)
            ->get()
            ->map(fn (CareCall $call) => [
                'key' => 'call-'.$call->id,
                'source' => 'call',
                'id' => $call->id,
                'label' => $call->displayLabel(),
                'scheduled_start' => $call->scheduled_start,
                'scheduled_end' => $call->scheduled_end,
                'actual_start' => $call->actual_start,
                'actual_end' => $call->actual_end,
                'status' => CareCallStatus::compute(
                    $call->scheduled_start,
                    $call->scheduled_end,
                    $call->actual_start,
                    $call->actual_end,
                    $call->isCancelled(),
                ),
                'notes' => $call->notes,
            ]);

        $timeline = $shifts->concat($calls)->sortBy('scheduled_start')->values();

        return view('livewire.care-timeline.care-call-timeline', [
            'day' => $day,
            'isToday' => $isToday,
            'isCarer' => $isCarer,
            'timeline' => $timeline,
        ]);
    }
}
