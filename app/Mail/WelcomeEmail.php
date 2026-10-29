<?php

namespace App\Mail;

use App\Models\User;
use App\Services\PlatformSettingsService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WelcomeEmail extends Mailable
{
    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to '.app(PlatformSettingsService::class)->current()->display_name);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',
            with: ['dashboardUrl' => route('dashboard')],
        );
    }
}
