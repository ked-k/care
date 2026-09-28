<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent whenever someone gets a reason to log in to CareTrust for the first
 * time, or gets linked to a new service user: a new staff account, a new
 * family login, or an existing family login being attached to another
 * service user.
 *
 * $plainPassword is only ever set right after the account itself was
 * created (we never store or re-derive a password later), so an email for
 * an existing account being re-linked correctly omits it — the recipient
 * already has a password and this mail is just telling them what changed.
 */
class AccountAccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $plainPassword = null,
        public ?string $serviceUserName = null,
        public ?string $relationship = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $agencyName = $this->user->agency?->name ?: config('app.name');

        $subject = $this->serviceUserName
            ? __(':agency: you now have access to :name\'s updates', [
                'agency' => $agencyName,
                'name' => $this->serviceUserName,
            ])
            : __('Your :agency login details', ['agency' => $agencyName]);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-access',
            with: [
                'user' => $this->user,
                'plainPassword' => $this->plainPassword,
                'serviceUserName' => $this->serviceUserName,
                'relationship' => $this->relationship,
                'agencyName' => $this->user->agency?->name ?: config('app.name'),
                'loginUrl' => route('login'),
            ],
        );
    }
}
