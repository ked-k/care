<?php

namespace App\Livewire\CareTimeline;

use App\Models\Shift;
use App\Models\CareCall;
use App\Models\ServiceUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Batch 14: the carer-only "client profile" screen the Care Call Timeline
 * needed a home on — carers previously had no standalone way to open a
 * service user's page independent of one shift (My Rota only ever links
 * into a single ShiftVisitComponent). Entry points added from My Rota, the
 * carer home's next-shift card, and the Notes/Messages pickers.
 *
 * Deliberately thin: the heavy lifting is the nested
 * CareCallTimelineComponent (today's calls) plus links out to the carer's
 * existing Notes and Messages screens for this same service user, rather
 * than duplicating either.
 */
class ClientProfileComponent extends Component
{
    public string $serviceUserId;

    public function mount(string $serviceUserId): void
    {
        $this->serviceUserId = $serviceUserId;

        abort_unless(Auth::user()->isCarerOnly(), 403, __('This page is for carers.'));
        abort_unless($this->isAssigned(), 403, __("You're not assigned to this person."));
    }

    protected function isAssigned(): bool
    {
        $carerId = Auth::id();

        return Shift::where('assigned_to', $carerId)->where('service_user_id', $this->serviceUserId)->exists()
            || CareCall::where('assigned_to', $carerId)->where('service_user_id', $this->serviceUserId)->exists();
    }

    public function render()
    {
        $serviceUser = ServiceUser::where('agency_id', Auth::user()->agency_id)->findOrFail($this->serviceUserId);

        return view('livewire.care-timeline.client-profile', [
            'serviceUser' => $serviceUser,
        ])->layout('layouts.carer');
    }
}
