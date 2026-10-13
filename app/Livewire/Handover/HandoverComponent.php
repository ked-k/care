<?php

namespace App\Livewire\Handover;

use App\Models\Alert;
use App\Models\CareTimelineEntry;
use App\Models\ServiceUser;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Batch 17: "a dedicated handover view that packages outstanding tasks
 * and important updates for the next carer, plus a general flag-as-urgent
 * escalation route that doesn't require going through safeguarding."
 * Nested on both the carer's shift-visit screen and the standalone
 * client-profile page, same pattern as App\Livewire\CareTimeline\
 * CareCallTimelineComponent and the new WellbeingObservationComponent.
 *
 * Handover notes are always internal (visible_to_family is hardcoded
 * false here) — unlike the carer's existing quick Notes, which the carer
 * chooses whether to share, a handover note is specifically "for whoever
 * picks this up next", not for the family. "Flag as urgent" is the escape
 * hatch the gap assessment called for: any carer can raise it for any
 * reason, no safeguarding form, no incident type to pick — it just needs
 * a named person to see it and respond.
 */
class HandoverComponent extends Component
{
    public string $serviceUserId;
    public ?string $shiftTakeoverId = null;

    public string $formHandoverNote = '';
    public string $formUrgentSummary = '';

    public function mount(string $serviceUserId, ?string $shiftTakeoverId = null): void
    {
        $this->serviceUserId = $serviceUserId;
        $this->shiftTakeoverId = $shiftTakeoverId;
    }

    protected function serviceUser(): ServiceUser
    {
        return ServiceUser::where('agency_id', Auth::user()->agency_id)->findOrFail($this->serviceUserId);
    }

    /**
     * Tasks for this person's active care plan(s) with no log yet, due
     * today or already overdue — "what's left for the next carer to
     * pick up", not the person's entire future task backlog.
     */
    #[Computed]
    public function outstandingTasks()
    {
        return Task::with('carePlan')
            ->whereHas('carePlan', fn ($q) => $q->where('service_user_id', $this->serviceUserId)->where('is_active', true))
            ->whereDoesntHave('logs')
            ->where(fn ($q) => $q->whereDate('due_at', '<=', now()->toDateString())->orWhereNull('due_at'))
            ->orderByDesc('priority')
            ->orderBy('due_at')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function handoverNotes()
    {
        return CareTimelineEntry::where('service_user_id', $this->serviceUserId)
            ->where('entry_type', 'handover')
            ->with('creator')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    public function addHandoverNote(): void
    {
        $this->validate(['formHandoverNote' => 'required|string|max:2000']);

        CareTimelineEntry::create([
            'service_user_id' => $this->serviceUserId,
            'shift_takeover_id' => $this->shiftTakeoverId,
            'entry_type' => 'handover',
            'content' => $this->formHandoverNote,
            'visible_to_family' => false,
            'created_by' => Auth::id(),
        ]);

        $this->reset(['formHandoverNote']);
        $this->dispatch('toast', message: 'Handover note added.', type: 'success');
    }

    public function flagUrgent(): void
    {
        $this->validate(['formUrgentSummary' => 'required|string|max:1000']);

        $serviceUser = $this->serviceUser();

        Alert::raiseFor(
            $serviceUser,
            Alert::TYPE_URGENT_CONCERN,
            sprintf('%s (%s): %s', __('Flagged as urgent'), $serviceUser->name, $this->formUrgentSummary),
        );

        CareTimelineEntry::create([
            'service_user_id' => $serviceUser->id,
            'shift_takeover_id' => $this->shiftTakeoverId,
            'entry_type' => 'handover',
            'content' => __('Flagged as urgent').': '.$this->formUrgentSummary,
            'visible_to_family' => false,
            'created_by' => Auth::id(),
        ]);

        $this->reset(['formUrgentSummary']);
        $this->dispatch('close-drawer', 'flag-urgent-form');
        $this->dispatch('toast', message: 'Flagged as urgent — an alert has been sent.', type: 'success');
    }

    public function render()
    {
        return view('livewire.handover.handover');
    }
}
