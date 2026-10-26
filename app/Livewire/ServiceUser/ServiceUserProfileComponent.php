<?php

namespace App\Livewire\ServiceUser;

use App\Models\CarePlan;
use App\Models\MediaFile;
use App\Models\ServiceUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

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
    use WithFileUploads;

    public string $serviceUserId;

    // Batch 39: a profile photo and a signed care plan PDF — see the
    // migrations' doc comments. Gated separately from plain viewing
    // (canAccessServiceUser(), below) because uploading an official
    // document/photo is an office action, not something every carer who
    // happens to have a shift with this person should be able to do —
    // same gate StaffManagerComponent already uses for staff management.
    public bool $canManage = false;

    public $photoUpload = null;

    public $signedDocumentUpload = null;

    public function mount(string $serviceUserId): void
    {
        $this->serviceUserId = $serviceUserId;

        $serviceUser = ServiceUser::withTrashed()->findOrFail($serviceUserId);

        abort_unless(
            Auth::user()->canAccessServiceUser($serviceUser),
            403,
            __("You don't have access to this person's record.")
        );

        $this->canManage = Auth::user()->can('manage_user') || Auth::user()->hasAnyRole(['Admin', 'Super Admin', 'Manager']);
    }

    public function uploadPhoto(): void
    {
        abort_unless($this->canManage, 403);

        $this->validate(['photoUpload' => 'required|image|max:2048']);

        $serviceUser = ServiceUser::withTrashed()->findOrFail($this->serviceUserId);
        $serviceUser->update(['photo_path' => $this->photoUpload->store('service-user-photos', 'public')]);

        $this->reset(['photoUpload']);
        $this->dispatch('toast', message: 'Photo updated.', type: 'success');
    }

    public function removePhoto(): void
    {
        abort_unless($this->canManage, 403);

        ServiceUser::withTrashed()->findOrFail($this->serviceUserId)->update(['photo_path' => null]);
        $this->dispatch('toast', message: 'Photo removed.', type: 'success');
    }

    /**
     * A signed (scanned/wet-signature) copy of this care plan — separate
     * from the Batch 37 "Download PDF" button, which always regenerates a
     * fresh copy of the live record rather than storing an actual signed
     * document. Two-step MediaFile attachment, same convention as
     * training certificates.
     */
    public function uploadSignedDocument(string $carePlanId): void
    {
        abort_unless($this->canManage, 403);

        $this->validate(['signedDocumentUpload' => 'required|file|mimes:pdf,jpg,jpeg,png|max:8192']);

        $plan = CarePlan::where('service_user_id', $this->serviceUserId)->findOrFail($carePlanId);

        $path = $this->signedDocumentUpload->store('signed-care-plans', 'public');

        $media = MediaFile::create([
            'file_name' => $this->signedDocumentUpload->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $this->signedDocumentUpload->getMimeType(),
            'file_size' => $this->signedDocumentUpload->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        $plan->update(['signed_document_media_id' => $media->id]);

        MediaFile::whereKey($media->id)->update([
            'related_type' => CarePlan::class,
            'related_id' => $plan->id,
        ]);

        $this->reset(['signedDocumentUpload']);
        $this->dispatch('toast', message: 'Signed care plan uploaded.', type: 'success');
    }

    public function removeSignedDocument(string $carePlanId): void
    {
        abort_unless($this->canManage, 403);

        CarePlan::where('service_user_id', $this->serviceUserId)->findOrFail($carePlanId)
            ->update(['signed_document_media_id' => null]);

        $this->dispatch('toast', message: 'Signed document removed.', type: 'success');
    }

    public function render()
    {
        $serviceUser = ServiceUser::withTrashed()
            ->with(['careCoordinator', 'agency'])
            ->findOrFail($this->serviceUserId);

        // Batch 38: routineOnly() — one row per DEFINED task, not every
        // occurrence tasks:generate-occurrences has created going forward.
        // Before this, a 24-hour routine with 6-8 tasks a day could put
        // hundreds of near-identical rows on this page (and, worse, in the
        // printed/PDF version) once 30+ days of occurrences existed — see
        // Task::scopeRoutineOnly()'s doc comment and CHANGES38.md.
        $carePlans = $serviceUser->carePlans()
            ->where('is_active', true)
            ->with(['tasks' => fn ($q) => $q->routineOnly()->orderBy('due_at'), 'signedDocument'])
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
