<?php

namespace App\Mail;

use App\Models\Minute;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MinuteServiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Minute $minute,
        private readonly string $pdfContents,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Minuta de servicio {$this->minute->folio} - {$this->minute->client_name}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.minute');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => $this->pdfContents,
                "{$this->minute->folio}.pdf",
            )->withMime('application/pdf'),
        ];
    }
}
