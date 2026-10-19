<?php

namespace App\Mail;

use App\Models\Shift;
use App\Models\User;
use App\Models\VisitCheckin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Batch 35: sent to every Admin/Manager/Super Admin at the agency whenever
 * a carer checks in outside the geofence (or sends no location at all) and
 * the agency's "allow remote check-in" setting lets the check-in through
 * anyway rather than blocking it — see
 * App\Livewire\Rota\ShiftVisitComponent::checkIn(). Alongside the in-app
 * notification App\Services\NotificationService already sends for the
 * same event, so it's visible even to someone not currently logged in.
 */
class RemoteCheckinMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public User $carer,
        public Shift $shift,
        public VisitCheckin $checkin,
        public ?string $deviationReason,
    ) {
    }

    public function envelope(): Envelope
    {
        $clientName = $this->shift->serviceUser->name ?? __('an unassigned visit');

        return new Envelope(
            subject: __('Remote check-in: :carer for :client', [
                'carer' => $this->carer->name,
                'client' => $clientName,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.remote-checkin',
            with: [
                'recipient' => $this->recipient,
                'carer' => $this->carer,
                'shift' => $this->shift,
                'checkin' => $this->checkin,
                'deviationReason' => $this->deviationReason,
                'clientName' => $this->shift->serviceUser->name ?? __('an unassigned visit'),
                'agencyName' => $this->recipient->agency?->name ?: config('app.name'),
            ],
        );
    }
}
