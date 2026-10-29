<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WorkflowAlertMail extends Mailable
{
    public function __construct(public readonly string $title, public readonly string $alertMessage, public readonly ?string $actionUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->title);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.workflow-alert');
    }
}
