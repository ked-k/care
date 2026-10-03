<?php

namespace App\Livewire\Family;

use App\Mail\AccountAccessMail;
use App\Models\FamilyMember;
use App\Models\ServiceUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Staff-side screen for linking a family member's own login to a service
 * user. Family members have no accounts today, so the primary path creates
 * one (role "Family") alongside the link; a family member already linked to
 * another service user can also just be re-linked by email.
 */
class FamilyMemberManagerComponent extends Component
{
    public string $serviceUserId;

    // Add-family-member form (drawer) state
    public string $formName = '';
    public string $formEmail = '';
    public string $formRelationship = '';
    public bool $formIsPrimaryContact = false;
    public bool $formCanReceiveUpdates = true;

    public ?string $generatedPassword = null;
    public bool $emailedOk = true;

    public function mount(string $serviceUserId): void
    {
        abort_unless(Auth::user()->canAccessServiceUser(ServiceUser::findOrFail($serviceUserId)), 403, __("You don't have access to this person's record."));
        $this->serviceUserId = $serviceUserId;
    }

    protected function serviceUser(): ServiceUser
    {
        return ServiceUser::where('agency_id', Auth::user()->agency_id)->findOrFail($this->serviceUserId);
    }

    public function canManage(): bool
    {
        $user = Auth::user();
        return $user->can('family.manage') || $user->hasRole(['Admin', 'Super Admin']);
    }

    protected function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only a manager can manage family access.');
    }

    public function openAddForm(): void
    {
        $this->authorizeManage();
        $this->reset(['formName', 'formEmail', 'formRelationship', 'formIsPrimaryContact']);
        $this->formCanReceiveUpdates = true;
        $this->generatedPassword = null;
        $this->emailedOk = true;
        $this->resetErrorBag();
        $this->dispatch('open-drawer', 'family-member-form');
    }

    public function addFamilyMember(): void
    {
        $this->authorizeManage();

        $this->validate([
            'formName' => 'required|string|max:255',
            'formEmail' => 'required|email|max:255',
            'formRelationship' => 'required|string|max:255',
        ]);

        $serviceUser = $this->serviceUser();

        $familyUser = User::where('email', $this->formEmail)->first();
        $plainPassword = null;

        if (! $familyUser) {
            $plainPassword = Str::password(12);
            $familyUser = User::create([
                'uid' => (string) Str::uuid(),
                'name' => $this->formName,
                'first_name' => $this->formName,
                'email' => $this->formEmail,
                'password' => Hash::make($plainPassword),
                'agency_id' => $serviceUser->agency_id,
                'is_active' => true,
                'created_by' => Auth::id(),
            ]);
            $familyUser->assignRole('Family');
        } elseif (! $familyUser->hasRole('Family')) {
            $familyUser->assignRole('Family');
        }

        $familyMember = FamilyMember::updateOrCreate(
            ['service_user_id' => $serviceUser->id, 'user_id' => $familyUser->id],
            [
                'relationship' => $this->formRelationship,
                'is_primary_contact' => $this->formIsPrimaryContact,
                'can_receive_updates' => $this->formCanReceiveUpdates,
                'created_by' => Auth::id(),
            ]
        );

        $this->generatedPassword = $plainPassword;

        // Only email when there's actually news to tell them: a brand-new
        // account, or a brand-new link to this service user. Re-saving an
        // existing link's checkboxes (is_primary_contact, etc.) shouldn't
        // re-send a "you now have access" email.
        $this->emailedOk = true;
        if ($plainPassword || $familyMember->wasRecentlyCreated) {
            $this->emailedOk = $this->sendAccessEmail($familyUser, $plainPassword, $serviceUser->name, $this->formRelationship);
        }

        if (! $plainPassword) {
            $this->dispatch('close-drawer', 'family-member-form');
            $this->dispatch('toast', message: $this->emailedOk
                ? 'Family member linked. Access details emailed.'
                : 'Family member linked. (Could not send the notification email — check mail settings.)',
                type: $this->emailedOk ? 'success' : 'warning');
        }
        // If a new account was created, the drawer stays open showing the
        // one-time password — see the view — until the manager dismisses it.
    }

    protected function sendAccessEmail(User $user, ?string $plainPassword, string $serviceUserName, string $relationship): bool
    {
        try {
            Mail::to($user->email)->send(new AccountAccessMail(
                user: $user,
                plainPassword: $plainPassword,
                serviceUserName: $serviceUserName,
                relationship: $relationship,
            ));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to send family access email: '.$e->getMessage());

            return false;
        }
    }

    public function dismissGeneratedPassword(): void
    {
        $this->generatedPassword = null;
        $this->dispatch('close-drawer', 'family-member-form');
        $this->dispatch('toast', message: $this->emailedOk
            ? 'Family member linked. Access details emailed.'
            : 'Family member linked. (Could not send the notification email — check mail settings.)',
            type: $this->emailedOk ? 'success' : 'warning');
    }

    public function removeFamilyMember(string $familyMemberId): void
    {
        $this->authorizeManage();
        FamilyMember::whereKey($familyMemberId)->delete();
        $this->dispatch('toast', message: 'Family member removed.', type: 'warning');
    }

    /**
     * For "they never got the original email, or lost it": generates a
     * fresh password for this family member's login and emails it again,
     * the same one-click reset a staff account can get from Staff
     * Management. We never store the original plaintext password, so this
     * is the only way to get them working credentials again short of them
     * using "forgot password" themselves.
     */
    public function resendAccess(string $familyMemberId): void
    {
        $this->authorizeManage();

        $familyMember = FamilyMember::with(['user', 'serviceUser'])->findOrFail($familyMemberId);

        // Belt-and-braces: authorizeManage() only checks the current admin's
        // role, not that this specific family link belongs to their agency.
        // Since this action changes a password (more sensitive than the
        // existing remove/list actions on this component), scope it
        // explicitly rather than relying on family_member ids being
        // practically unguessable UUIDs.
        abort_unless($familyMember->serviceUser?->agency_id === Auth::user()->agency_id, 403);

        $user = $familyMember->user;
        $newPassword = Str::password(12);
        $user->update(['password' => Hash::make($newPassword)]);

        $emailedOk = $this->sendAccessEmail($user, $newPassword, $familyMember->serviceUser->name, $familyMember->relationship);

        $this->dispatch('toast', message: $emailedOk
            ? 'New login details emailed to '.$user->email.'.'
            : 'Password reset, but the email could not be sent — check mail settings.',
            type: $emailedOk ? 'success' : 'warning');
    }

    public function render()
    {
        $serviceUser = $this->serviceUser();
        $familyMembers = $serviceUser->familyMembers()->with('user')->orderByDesc('is_primary_contact')->get();

        return view('livewire.family.family-member-manager', [
            'serviceUser' => $serviceUser,
            'familyMembers' => $familyMembers,
            'canManage' => $this->canManage(),
        ]);
    }
}
