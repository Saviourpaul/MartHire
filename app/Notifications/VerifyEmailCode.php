<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

class VerifyEmailCode extends Notification
{
    public function __construct(#[SensitiveParameter] public readonly string $code) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your MartHire email verification code')
            ->markdown('emails.auth.verify', [
                'user' => $notifiable,
                'code' => $this->code,
                'expires' => (int) config('auth.verification.expire', 15),
            ]);
    }
}
