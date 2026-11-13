<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationDemoSetupInvitation extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $url,
        private readonly string $contactName,
        private readonly string $companyName,
        private readonly int $expiresInDays,
    ) {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your MarHire organization account is approved')
            ->greeting('Hello '.$this->contactName.',')
            ->line('MarHire has approved the request for '.$this->companyName.'.')
            ->line('Use the secure link below to create your organization account and start setup.')
            ->action('Set up your MarHire account', $this->url)
            ->line('This single-use link expires in '.$this->expiresInDays.' days. If you did not request this, you can ignore this message.');
    }
}
