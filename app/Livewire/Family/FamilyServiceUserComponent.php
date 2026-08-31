<?php

namespace App\Livewire\Family;

use App\Models\FamilyMember;
use App\Models\ServiceUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.family')]
class FamilyServiceUserComponent extends Component
{
    public string $serviceUserId;

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
    }

    public function render()
    {
        $serviceUser = ServiceUser::findOrFail($this->serviceUserId);

        $carePlans = $serviceUser->carePlans()->where('is_active', true)->orderByDesc('review_date')->get();

        $timeline = $serviceUser->careTimelineEntries()
            ->visibleToFamily()
            ->with('creator')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return view('livewire.family.family-service-user', [
            'serviceUser' => $serviceUser,
            'carePlans' => $carePlans,
            'timeline' => $timeline,
        ]);
    }
}
