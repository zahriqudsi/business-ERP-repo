<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * An invoice, quotation, job note or statement sent to a customer as a PDF attachment.
 */
class DocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $messageText,
        public string $fileName,
        public string $pdfContent,
    ) {}

    public function envelope(): Envelope
    {
        // Sent from the platform's own address (so it passes SPF/DKIM) under the business's name; replies go to the business
        return new Envelope(
            from: new Address(config('mail.from.address'), config('sjpos.company.name')),
            subject: $this->subjectLine,
            replyTo: [new Address(config('sjpos.company.email'), config('sjpos.company.name'))],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.document');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->fileName)->withMime('application/pdf'),
        ];
    }
}
