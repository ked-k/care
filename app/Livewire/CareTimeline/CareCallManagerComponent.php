<?php

namespace App\Livewire\CareTimeline;

use App\Models\CareCall;
use App\Models\ServiceUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Batch 14: manager/admin page for scheduling the extra welfare calls
 * (lunch/evening/bedtime style) a service user needs beyond their one
 * staffing Shift — reached from the Service Users list next to
 * Family/Messages/Assessments/Timeline. Scoped to one date at a time
 * (prev/next, like the MAR chart's week nav) rather than a recurring-
 * schedule generator — if a client needs the same four calls every day,
 * that's a real follow-up worth doing once this is in use, deliberately
 * not built here.
 *
 * Shows the same Care Call Timeline carers see for this date at the top
 * (so a manager can preview exactly what the carer will see), with the
 * create/edit/cancel/delete form below it.
 */
class CareCallManagerComponent extends Component
{
    public string $serviceUserId;
    public string $date;

    public ?string $editingCallId = null;
    public string $formLabel = '';
    public string $formStart = '';
    public string $formEnd = '';
    public string $formCarerId = '';
    public string $formNotes = '';

    public function mount(string $serviceUserId): void
    {
        $this->serviceUserId = $serviceUserId;
        $this->date = today()->toDateString();
        $this->serviceUser(); // 404s if not in this manager's agency
    }

    protected function serviceUser(): ServiceUser
    {
        return ServiceUser::where('agency_id', Auth::user()->agency_id)->findOrFail($this->serviceUserId);
    }

    protected function carers()
    {
        return User::where('agency_id', Auth::user()->agency_id)->orderBy('name')->pluck('name', 'id');
    }

    public function previousDay(): void
    {
        $this->date = Carbon::parse($this->date)->subDay()->toDateString();
    }

    public function nextDay(): void
    {
        $this->date = Carbon::parse($this->date)->addDay()->toDateString();
    }

    public function goToday(): void
    {
        $this->date = today()->toDateString();
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->dispatch('open-drawer', 'care-call-form');
    }

    public function openEditForm(string $callId): void
    {
        $call = CareCall::where('service_user_id', $this->serviceUserId)->findOrFail($callId);

        $this->editingCallId = $call->id;
        $this->formLabel = $call->call_label ?? '';
        $this->formStart = $call->scheduled_start->format('H:i');
        $this->formEnd = $call->scheduled_end->format('H:i');
        $this->formCarerId = $call->assigned_to ? (string) $call->assigned_to : '';
        $this->formNotes = $call->notes ?? '';
        $this->dispatch('open-drawer', 'care-call-form');
    }

    protected function resetForm(): void
    {
        $this->reset(['editingCallId', 'formLabel', 'formStart', 'formEnd', 'formCarerId', 'formNotes']);
    }

    public function saveCall(): void
    {
        $this->validate([
            'formLabel' => 'nullable|string|max:40',
            'formStart' => 'required',
            'formEnd' => 'required',
            'formCarerId' => 'nullable',
            'formNotes' => 'nullable|string|max:2000',
        ]);

        $start = Carbon::parse("{$this->date} {$this->formStart}");
        $end = Carbon::parse("{$this->date} {$this->formEnd}");

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $serviceUser = $this->serviceUser();

        $data = [
            'agency_id' => $serviceUser->agency_id,
            'service_user_id' => $serviceUser->id,
            'assigned_to' => $this->formCarerId ?: null,
            'call_label' => $this->formLabel ?: null,
            'scheduled_start' => $start,
            'scheduled_end' => $end,
            'notes' => $this->formNotes ?: null,
            'updated_by' => Auth::id(),
        ];

        if ($this->editingCallId) {
            CareCall::where('service_user_id', $this->serviceUserId)->whereKey($this->editingCallId)->update($data);
        } else {
            $data['created_by'] = Auth::id();
            CareCall::create($data);
        }

        $this->resetForm();
        $this->dispatch('close-drawer', 'care-call-form');
        $this->dispatch('toast', message: __('Call saved.'), type: 'success');
    }

    public function deleteCall(string $callId): void
    {
        CareCall::where('service_user_id', $this->serviceUserId)->whereKey($callId)->delete();
        $this->dispatch('toast', message: __('Call removed.'), type: 'warning');
    }

    public function render()
    {
        $serviceUser = $this->serviceUser();

        $calls = CareCall::where('service_user_id', $this->serviceUserId)
            ->whereDate('scheduled_start', $this->date)
            ->orderBy('scheduled_start')
            ->get();

        return view('livewire.care-timeline.care-call-manager', [
            'serviceUser' => $serviceUser,
            'calls' => $calls,
            'carers' => $this->carers(),
        ]);
    }
}
