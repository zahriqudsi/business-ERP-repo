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
class FindWorkspaceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{name: string, url: string}>  $workspaces
     */
    public function __construct(public array $workspaces) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('sjpos.product_name')),
            subject: 'Your '.config('sjpos.product_name').' sign-in addresses',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.find-workspace');
    }
}
