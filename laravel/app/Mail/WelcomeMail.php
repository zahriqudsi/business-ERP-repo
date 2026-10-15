<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $businessName,
        public string $loginUrl,
        public string $username,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->businessName} is ready on ".config('sjpos.product_name'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.welcome');
    }
}
