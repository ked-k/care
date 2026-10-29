<?php

namespace App\Mail\Shop;

use App\Models\Shop\ShopCustomer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ShopCustomer $customer, public string $url)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __(':store: reset your password', ['store' => $this->customer->store->name]));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shop.password-reset',
            with: ['customer' => $this->customer, 'store' => $this->customer->store, 'url' => $this->url],
        );
    }
}
