<?php

namespace App\Livewire\Family;

use App\Models\FamilyMember;
use App\Models\Shift;
use App\Models\ServiceUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The family portal's main screen for one linked service user. Rebuilt in
 * Batch 9 from a single "care plan + recent updates" page into a tabbed,
 * mobile-app-style view — Overview / Care Plan / Medications / Schedule /
 * Notes — so family gets the fuller picture the vision doc always described
 * (medications, schedules, programs) rather than just a timeline. Batch 10
 * adds a sixth tab, Messages, backed by its own nested FamilyChatComponent.
 *
 * Everything here is still read-only and still scoped by the same two
 * rules as before: only data for a service user this login is explicitly
 * linked to (enforced in mount()), and only timeline entries flagged
 * visible_to_family. Medication detail has a third gate on top — an active
 * "medication_communication" consent — since that's more sensitive than a
 * general update and the consent model already exists to express exactly
 * this choice.
 */
#[Layout('layouts.family')]
class FamilyServiceUserComponent extends Component
{
    public string $serviceUserId;

    #[Url(as: 'tab', history: true)]
    public string $tab = 'overview';

    public function mount(string $serviceUserId): void
    {
        abort_unless(Auth::user()->hasRole('Family'), 403);

        $this->serviceUserId = $serviceUserId;

        // A family login only ever sees the service user(s) they're
        // explicitly linked to — never the wider agency's records.
        abort_unless(
            FamilyMember::where('service_user_id', $serviceUserId)->where('user_id', Auth::id())->exists(),
            403,
            "You don't have access to this person's record."
        );

        if (! in_array($this->tab, ['overview', 'care-plan', 'medications', 'schedule', 'notes', 'messages'], true)) {
            $this->tab = 'overview';
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function render()
    {
        $serviceUser = ServiceUser::with('agency')->findOrFail($this->serviceUserId);

        $carePlans = $serviceUser->carePlans()
            ->where('is_active', true)
            ->with(['tasks' => fn ($q) => $q->orderBy('due_at')->with('latestLog')])
            ->orderByDesc('review_date')
            ->get();

        $canSeeMedications = $serviceUser->hasActiveMedicationConsent();

        $medications = $canSeeMedications
            ? $serviceUser->medications()
                ->where('is_active', true)
                ->with(['administrations' => fn ($q) => $q->orderByDesc('scheduled_time')->limit(5)])
                ->orderBy('medication_name')
                ->get()
            : collect();

        // Mirrors MyRotaComponent's own rule for carers: a draft rota period
        // is a manager's working copy and shouldn't be visible to anyone
        // outside staff until it's published.
        $publishedShifts = Shift::where('service_user_id', $serviceUser->id)
            ->whereHas('rotaPeriod', fn ($q) => $q->where('status', 'published'))
            ->with('carer');

        $upcomingShifts = (clone $publishedShifts)
            ->where('scheduled_start', '>=', now())
            ->orderBy('scheduled_start')
            ->limit(10)
            ->get();

        $recentShifts = (clone $publishedShifts)
            ->where('scheduled_start', '<', now())
            ->orderByDesc('scheduled_start')
            ->limit(10)
            ->get();

        $timeline = $serviceUser->careTimelineEntries()
            ->visibleToFamily()
            ->with('creator')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('livewire.family.family-service-user', [
            'serviceUser' => $serviceUser,
            'carePlans' => $carePlans,
            'canSeeMedications' => $canSeeMedications,
            'medications' => $medications,
            'upcomingShifts' => $upcomingShifts,
            'recentShifts' => $recentShifts,
            'nextShift' => $upcomingShifts->first(),
            'timeline' => $timeline,
            'unreadMessages' => $serviceUser->unreadMessagesCountForFamily(),
        ]);
    }
}
