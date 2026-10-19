<?php

namespace App\Livewire\ServiceUser;

use App\Models\ServiceUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Batch 37: the "full profile" page the Service Users list didn't have —
 * before this, seeing everything about one person meant opening up to
 * seven separate pages (Medications, Consents, Family, Messages,
 * Assessments, Timeline, Calls) plus the Edit drawer for basic/key-care
 * info, with no single screen a carer or office staff member could glance
 * at or print. This pulls the read-only essentials — basic details, key
 * care information, the active care plan(s) and their tasks, consents,
 * next of kin, care coordinator — onto one page, with a Print/Download
 * PDF button (same browser-print-dialog pattern Batch 7/21 already use —
 * no new dependency) and a "Get directions" link when the person has a
 * geocoded/pinned location (Batch 35).
 *
 * Deliberately leaves out anything with its own stricter access story —
 * safeguarding reports and billing/payer detail stay on their existing
 * pages, not duplicated here.
 *
 * Gate: the same App\Models\User::canAccessServiceUser() every other
 * service-user-scoped page already uses — Super Admin always, same-agency
 * staff, and a plain carer only for someone they actually have a shift
 * with.
 */
class ServiceUserProfileComponent extends Component
{
    public string $serviceUserId;

    public function mount(string $serviceUserId): void
    {
        $this->serviceUserId = $serviceUserId;

        $serviceUser = ServiceUser::withTrashed()->findOrFail($serviceUserId);

        abort_unless(
            Auth::user()->canAccessServiceUser($serviceUser),
            403,
            __("You don't have access to this person's record.")
        );
    }

    public function render()
    {
        $serviceUser = ServiceUser::withTrashed()
            ->with(['careCoordinator', 'agency'])
            ->findOrFail($this->serviceUserId);

        $carePlans = $serviceUser->carePlans()
            ->where('is_active', true)
            ->with(['tasks' => fn ($q) => $q->orderBy('due_at')])
            ->orderByDesc('review_date')
            ->get();

        $consents = $serviceUser->consents()->orderByDesc('granted_at')->get();

        $medications = $serviceUser->medications()->where('is_active', true)->orderBy('medication_name')->get();

        $familyMembers = $serviceUser->familyMembers()->with('user')->get();

        return view('livewire.service-user.service-user-profile', [
            'serviceUser' => $serviceUser,
            'carePlans' => $carePlans,
            'consents' => $consents,
            'medications' => $medications,
            'familyMembers' => $familyMembers,
        ]);
    }
}
