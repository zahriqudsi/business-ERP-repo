<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FindWorkspaceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{name: string, url: string}>  $workspaces
     */
    public function __construct(public array $workspaces) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your '.config('sjpos.product_name').' sign-in addresses');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.find-workspace');
    }
}
