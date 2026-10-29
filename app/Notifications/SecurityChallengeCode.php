<?php

namespace App\Notifications;

use App\Enums\SecurityActionPurpose;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

class SecurityChallengeCode extends Notification
{
    public function __construct(#[SensitiveParameter] public readonly string $code, public readonly SecurityActionPurpose $purpose) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your account security action')
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('Use this code to '.$this->purpose->label().':')
            ->line($this->code)
            ->line('This code expires in 15 minutes and works only for this action on your current device.')
            ->line('If you did not request this action, do not share the code.');
    }
}
