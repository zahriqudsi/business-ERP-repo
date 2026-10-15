<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent in the background (when a queue runs) from the platform, under the product name.
 */
class WelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $businessName,
        public string $loginUrl,
        public string $username,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('sjpos.product_name')),
            subject: "{$this->businessName} is ready on ".config('sjpos.product_name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.welcome');
    }
}
