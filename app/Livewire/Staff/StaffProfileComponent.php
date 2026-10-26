<?php

namespace App\Livewire\Staff;

use App\Models\MediaFile;
use App\Models\Notification;
use App\Models\StaffDocument;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Batch 39 — Staff Profile & Document Management: the client's own written
 * request for "a complete digital personnel file for every employee"
 * (recruitment docs, right to work, DBS, references, qualifications &
 * training, employment documents, additional records), plus the personal
 * details (name/contact/address/job title/start date) that go alongside
 * them.
 *
 * Reachable at /staff/{userId}/profile two ways: a manager opening it from
 * the Staff list (StaffManagerComponent), or a staff member opening their
 * own via a "My profile" link — the client explicitly asked for staff to
 * "view and upload their own documents for management approval", so this
 * one page serves both, with $canManage deciding what's editable rather
 * than two separate pages.
 *
 * Deliberately NOT in this batch (see CHANGES39.md for the full list and
 * why): a cross-staff compliance dashboard (StaffManagerComponent's list
 * gets a per-row badge instead, same as Service Users' list already has
 * one for key-care-info), "who viewed" audit logging (uploads/changes are
 * covered via StaffDocument's LogsActivity — viewing isn't tracked
 * anywhere in this app yet), and enforcement of the optional
 * retention_until date (UK GDPR retention periods need Affinity's own
 * policy input, not a guess).
 */
class StaffProfileComponent extends Component
{
    use WithFileUploads;

    public int $userId;

    public bool $canManage = false;

    public bool $isOwnProfile = false;

    // Upload-document form (drawer) state
    public string $formCategory = 'recruitment';
    public string $formTitle = '';
    public $formFile = null;
    public string $formIssuedAt = '';
    public string $formExpiresAt = '';

    // Per-row rejection note, keyed by document id — simplest way to pass
    // free text into rejectDocument() without a second drawer.
    public array $rejectNotes = [];

    // Employment start date quick-edit (manager only)
    public string $formEmploymentStartDate = '';

    // Avatar upload field — separate from $formFile above so the two
    // drawers never fight over the same bound property.
    public $avatarUpload = null;

    public function mount(string $userId): void
    {
        $this->userId = (int) $userId;

        $actingUser = Auth::user();
        $targetUser = User::findOrFail($this->userId);

        $this->canManage = $actingUser->can('manage_user') || $actingUser->hasAnyRole(['Admin', 'Super Admin']);
        $this->isOwnProfile = $actingUser->id === $targetUser->id;

        abort_unless($this->canManage || $this->isOwnProfile, 403, __("You don't have access to this profile."));

        // A manager only reaches into their own agency's staff — Super
        // Admin is the one exception, same rule canAccessServiceUser() uses.
        if (! $actingUser->hasRole('Super Admin') && $targetUser->agency_id !== $actingUser->agency_id) {
            abort(403, __("You don't have access to this profile."));
        }

        $this->formEmploymentStartDate = optional($targetUser->payProfile?->employment_start_date)->format('Y-m-d') ?? '';
    }

    protected function targetUser(): User
    {
        return User::findOrFail($this->userId);
    }

    protected function authorizeManage(): void
    {
        abort_unless($this->canManage, 403, __('Only a manager can do that.'));
    }

    public function openUploadForm(): void
    {
        $this->reset(['formCategory', 'formTitle', 'formFile', 'formIssuedAt', 'formExpiresAt']);
        $this->formCategory = 'recruitment';
        $this->dispatch('open-drawer', 'staff-document-form');
    }

    /**
     * Either a manager filing a document on someone's behalf (auto-approved
     * — a manager uploading it already is the approval), or a staff member
     * uploading their own (status starts 'pending' until a manager reviews
     * it) — see the class doc comment on why this one action serves both.
     */
    public function saveDocument(): void
    {
        $this->validate([
            'formCategory' => 'required|in:'.implode(',', array_keys(StaffDocument::CATEGORIES)),
            'formTitle' => 'required|string|max:255',
            'formFile' => 'required|file|mimes:pdf,jpg,jpeg,png|max:8192',
            'formIssuedAt' => 'nullable|date',
            'formExpiresAt' => 'nullable|date',
        ]);

        $targetUser = $this->targetUser();
        $actingUser = Auth::user();

        // Two-step MediaFile attachment — same convention as training
        // certificates (TrainingIndexComponent::logProgress()).
        $path = $this->formFile->store('staff-documents', 'public');

        $media = MediaFile::create([
            'file_name' => $this->formFile->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $this->formFile->getMimeType(),
            'file_size' => $this->formFile->getSize(),
            'uploaded_by' => $actingUser->id,
        ]);

        $document = StaffDocument::create([
            'agency_id' => $targetUser->agency_id,
            'user_id' => $targetUser->id,
            'category' => $this->formCategory,
            'title' => $this->formTitle,
            'media_id' => $media->id,
            'issued_at' => $this->formIssuedAt ?: null,
            'expires_at' => $this->formExpiresAt ?: null,
            'status' => $this->canManage ? StaffDocument::STATUS_APPROVED : StaffDocument::STATUS_PENDING,
            'uploaded_by' => $actingUser->id,
            'reviewed_by' => $this->canManage ? $actingUser->id : null,
            'reviewed_at' => $this->canManage ? now() : null,
            'created_by' => $actingUser->id,
        ]);

        MediaFile::whereKey($media->id)->update([
            'related_type' => StaffDocument::class,
            'related_id' => $document->id,
        ]);

        // A self-uploaded document needs a manager to actually see it —
        // notify whoever manages this agency's staff, same recipient rule
        // TrainingMatrixComponent::sendRenewalReminders() uses for
        // mandatory-training chasers.
        if (! $this->canManage) {
            $this->notifyManagers($targetUser, sprintf(
                '%s uploaded "%s" (%s) for review.',
                $targetUser->full_name, $this->formTitle, StaffDocument::categoryLabel($this->formCategory),
            ), 'staff_document_pending_review', ['staff_document_id' => $document->id]);
        }

        $this->reset(['formCategory', 'formTitle', 'formFile', 'formIssuedAt', 'formExpiresAt']);
        $this->dispatch('close-drawer', 'staff-document-form');
        $this->dispatch('toast', message: $this->canManage ? 'Document added.' : 'Uploaded — a manager will review it.', type: 'success');
    }

    public function approveDocument(string $documentId): void
    {
        $this->authorizeManage();

        $document = StaffDocument::where('user_id', $this->userId)->findOrFail($documentId);
        $document->update([
            'status' => StaffDocument::STATUS_APPROVED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'notes' => null,
            'updated_by' => Auth::id(),
        ]);

        $this->dispatch('toast', message: 'Document approved.', type: 'success');
    }

    public function rejectDocument(string $documentId): void
    {
        $this->authorizeManage();

        $document = StaffDocument::where('user_id', $this->userId)->findOrFail($documentId);
        $document->update([
            'status' => StaffDocument::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'notes' => $this->rejectNotes[$documentId] ?? null,
            'updated_by' => Auth::id(),
        ]);

        unset($this->rejectNotes[$documentId]);
        $this->dispatch('toast', message: 'Document rejected.', type: 'warning');
    }

    public function deleteDocument(string $documentId): void
    {
        $this->authorizeManage();

        StaffDocument::where('user_id', $this->userId)->findOrFail($documentId)->delete();

        $this->dispatch('toast', message: 'Document removed.', type: 'success');
    }

    public function saveEmploymentStartDate(): void
    {
        $this->authorizeManage();

        $this->validate(['formEmploymentStartDate' => 'nullable|date']);

        $targetUser = $this->targetUser();
        $profile = $targetUser->payProfile;

        // employee_pay_profiles.employee_no has no default and is required
        // — every real staff account already has one from
        // StaffManagerComponent's own create form, but guard against the
        // (otherwise silent SQL-error) edge case of a profile that was
        // somehow never set up rather than inventing an employee number
        // here.
        if (! $profile) {
            $this->dispatch('toast', message: 'This person has no pay profile yet — set up their Employee No. from the Staff list first.', type: 'warning');

            return;
        }

        $profile->update(['employment_start_date' => $this->formEmploymentStartDate ?: null]);

        $this->dispatch('toast', message: 'Employment start date saved.', type: 'success');
    }

    public function uploadAvatar(): void
    {
        $this->validate(['avatarUpload' => 'required|image|max:2048']);

        $targetUser = $this->targetUser();
        $path = $this->avatarUpload->store('avatars', 'public');

        $targetUser->update(['avatar' => $path]);

        $this->reset(['avatarUpload']);
        $this->dispatch('toast', message: 'Photo updated.', type: 'success');
    }

    public function removeAvatar(): void
    {
        $this->targetUser()->update(['avatar' => null]);
        $this->dispatch('toast', message: 'Photo removed.', type: 'success');
    }

    protected function notifyManagers(User $targetUser, string $message, string $type, array $data = []): void
    {
        $managerIds = User::where('agency_id', $targetUser->agency_id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Manager', 'Admin', 'Super Admin']))
            ->pluck('id');

        foreach ($managerIds as $managerId) {
            NotificationService::send($managerId, $type, __('Staff document needs review'), $message, Notification::PRIORITY_NORMAL, $data);
        }
    }

    public function render()
    {
        $targetUser = User::with('payProfile')->findOrFail($this->userId);

        $documents = StaffDocument::where('user_id', $this->userId)
            ->with(['media', 'uploader', 'reviewer'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('category');

        $compliance = StaffDocument::complianceSummaryFor($this->userId);

        return view('livewire.staff.staff-profile', [
            'targetUser' => $targetUser,
            'documents' => $documents,
            'compliance' => $compliance,
            'categories' => StaffDocument::CATEGORIES,
        ]);
    }
}
