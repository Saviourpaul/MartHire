<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationDemoRequestEmail extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    /** @param list<string> $lines */
    public function __construct(
        private readonly string $subject,
        private readonly string $greeting,
        private readonly array $lines,
        private readonly ?string $actionLabel = null,
        private readonly ?string $actionUrl = null,
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
        $message = (new MailMessage)->subject($this->subject)->greeting($this->greeting);

        foreach ($this->lines as $line) {
            $message->line($line);
        }

        if (filled($this->actionLabel) && filled($this->actionUrl)) {
            $message->action($this->actionLabel, $this->actionUrl);
        }

        return $message->salutation('The MarHire team');
    }
}
