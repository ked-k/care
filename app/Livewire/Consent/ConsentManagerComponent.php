<?php

namespace App\Livewire\Consent;

use App\Models\Consent;
use App\Models\ServiceUser;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ConsentManagerComponent extends Component
{
    public string $serviceUserId;

    // Grant-consent form (drawer) state
    public string $formConsentType = '';
    public bool $formGranted = true;
    public string $formExpiresAt = '';
    public string $formNotes = '';

    // Revoke form (drawer) state
    public string $revokingConsentId = '';
    public string $revokeNotes = '';

    public function mount(string $serviceUserId): void
    {
        $this->serviceUserId = $serviceUserId;
    }

    protected function serviceUser(): ServiceUser
    {
        $user = Auth::user();

        return ServiceUser::where('agency_id', $user->agency_id)->findOrFail($this->serviceUserId);
    }

    public function canManage(): bool
    {
        $user = Auth::user();
        return $user->can('consent.manage') || $user->hasRole(['Admin', 'Super Admin']);
    }

    protected function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only a manager can record consent.');
    }

    public function availableTypes(): array
    {
        return Consent::TYPES;
    }

    public function openRecordForm(): void
    {
        $this->authorizeManage();
        $this->reset(['formConsentType', 'formExpiresAt', 'formNotes']);
        $this->formGranted = true;
        $this->dispatch('open-drawer', 'consent-form');
    }

    public function recordConsent(): void
    {
        $this->authorizeManage();
        $this->validate([
            'formConsentType' => 'required|string|max:255',
            'formExpiresAt' => 'nullable|date',
            'formNotes' => 'nullable|string|max:2000',
        ]);

        $consent = Consent::create([
            'service_user_id' => $this->serviceUserId,
            'consent_type' => $this->formConsentType,
            'granted' => $this->formGranted,
            'granted_by' => Auth::id(),
            'granted_at' => $this->formGranted ? now() : null,
            'expires_at' => $this->formExpiresAt ?: null,
            'notes' => $this->formNotes ?: null,
            'created_by' => Auth::id(),
        ]);

        AuditLogger::log('CONSENT_RECORDED', $consent, ['type' => $this->formConsentType, 'granted' => $this->formGranted]);

        $this->dispatch('close-drawer', 'consent-form');
        $this->dispatch('toast', message: 'Consent recorded.', type: 'success');
    }

    public function openRevokeForm(string $consentId): void
    {
        $this->authorizeManage();
        $this->revokingConsentId = $consentId;
        $this->revokeNotes = '';
        $this->dispatch('open-drawer', 'consent-revoke');
    }

    public function revoke(): void
    {
        $this->authorizeManage();

        $consent = Consent::findOrFail($this->revokingConsentId);
        $consent->update([
            'revoked_at' => now(),
            'notes' => trim(($consent->notes ? $consent->notes."\n" : '')."Revoked: {$this->revokeNotes}"),
            'updated_by' => Auth::id(),
        ]);

        AuditLogger::log('CONSENT_REVOKED', $consent, ['type' => $consent->consent_type]);

        $this->dispatch('close-drawer', 'consent-revoke');
        $this->dispatch('toast', message: 'Consent revoked.', type: 'warning');
    }

    public function render()
    {
        $serviceUser = $this->serviceUser();
        $consents = $serviceUser->consents()->orderByDesc('created_at')->get();

        return view('livewire.consent.consent-manager', [
            'serviceUser' => $serviceUser,
            'consents' => $consents,
            'canManage' => $this->canManage(),
        ]);
    }
}
