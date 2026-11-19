<?php

namespace App\Mail;

use App\Models\PolicyAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PolicyThresholdAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PolicyAlert $alert) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Alerta: póliza {$this->alert->policy_number} con {$this->alert->remaining_percentage}% disponible",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.policy-alert');
    }
}
