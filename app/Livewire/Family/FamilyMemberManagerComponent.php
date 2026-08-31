<?php

namespace App\Livewire\Family;

use App\Models\FamilyMember;
use App\Models\ServiceUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

    public function mount(string $serviceUserId): void
    {
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

        FamilyMember::updateOrCreate(
            ['service_user_id' => $serviceUser->id, 'user_id' => $familyUser->id],
            [
                'relationship' => $this->formRelationship,
                'is_primary_contact' => $this->formIsPrimaryContact,
                'can_receive_updates' => $this->formCanReceiveUpdates,
                'created_by' => Auth::id(),
            ]
        );

        $this->generatedPassword = $plainPassword;

        if (! $plainPassword) {
            $this->dispatch('close-drawer', 'family-member-form');
            $this->dispatch('toast', message: 'Family member linked.', type: 'success');
        }
        // If a new account was created, the drawer stays open showing the
        // one-time password — see the view — until the manager dismisses it.
    }

    public function dismissGeneratedPassword(): void
    {
        $this->generatedPassword = null;
        $this->dispatch('close-drawer', 'family-member-form');
        $this->dispatch('toast', message: 'Family member linked.', type: 'success');
    }

    public function removeFamilyMember(string $familyMemberId): void
    {
        $this->authorizeManage();
        FamilyMember::whereKey($familyMemberId)->delete();
        $this->dispatch('toast', message: 'Family member removed.', type: 'warning');
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
