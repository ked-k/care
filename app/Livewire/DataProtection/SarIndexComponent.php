<?php

namespace App\Livewire\DataProtection;

use App\Models\ServiceUser;
use App\Models\SubjectAccessRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SarIndexComponent extends Component
{
    public string $formServiceUserId = '';
    public string $formType = 'access';
    public string $formNotes = '';

    public string $resolvingId = '';
    public string $resolveStatus = 'fulfilled';
    public string $resolveNotes = '';

    public function canManage(): bool
    {
        $user = Auth::user();
        return $user->can('data-protection.manage') || $user->hasRole(['Admin', 'Super Admin']);
    }

    protected function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only a manager can process data requests.');
    }

    public function serviceUserOptions(): array
    {
        return ServiceUser::where('agency_id', Auth::user()->agency_id)->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function openCreateForm(): void
    {
        $this->reset(['formServiceUserId', 'formNotes']);
        $this->formType = 'access';
        $this->dispatch('open-drawer', 'sar-form');
    }

    public function submitRequest(): void
    {
        $this->validate([
            'formServiceUserId' => 'required|exists:service_users,id',
            'formType' => 'required|string|max:255',
            'formNotes' => 'nullable|string|max:2000',
        ]);

        // Batch 20: creation is now logged automatically by
        // SubjectAccessRequest's LogsActivity trait.
        SubjectAccessRequest::create([
            'requested_by' => Auth::id(),
            'service_user_id' => $this->formServiceUserId,
            'type' => $this->formType,
            'notes' => $this->formNotes ?: null,
            'created_by' => Auth::id(),
        ]);

        $this->reset(['formServiceUserId', 'formNotes']);
        $this->dispatch('close-drawer', 'sar-form');
        $this->dispatch('toast', message: 'Request logged.', type: 'success');
    }

    public function openResolveForm(string $sarId): void
    {
        $this->authorizeManage();
        $this->resolvingId = $sarId;
        $this->resolveStatus = 'fulfilled';
        $this->resolveNotes = '';
        $this->dispatch('open-drawer', 'sar-resolve');
    }

    public function resolve(): void
    {
        $this->authorizeManage();
        $this->validate([
            'resolveStatus' => 'required|in:in_progress,fulfilled,rejected',
            'resolveNotes' => 'nullable|string|max:2000',
        ]);

        $sar = SubjectAccessRequest::findOrFail($this->resolvingId);

        // Batch 20: the status change is now logged automatically (old/new
        // status) by SubjectAccessRequest's LogsActivity trait.
        $sar->update([
            'status' => $this->resolveStatus,
            'fulfilled_by' => in_array($this->resolveStatus, ['fulfilled', 'rejected']) ? Auth::id() : null,
            'notes' => trim(($sar->notes ? $sar->notes."\n" : '').$this->resolveNotes),
            'updated_by' => Auth::id(),
        ]);

        $this->reset(['resolvingId', 'resolveStatus', 'resolveNotes']);
        $this->dispatch('close-drawer', 'sar-resolve');
        $this->dispatch('toast', message: 'Request updated.', type: 'success');
    }

    public function render()
    {
        $agencyId = Auth::user()->agency_id;

        $requests = SubjectAccessRequest::with(['requester', 'serviceUser', 'fulfiller'])
            ->whereHas('serviceUser', fn ($q) => $q->where('agency_id', $agencyId))
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.data-protection.sar-index', [
            'requests' => $requests,
            'canManage' => $this->canManage(),
        ]);
    }
}
