<?php

namespace App\Notifications;

use App\Services\PlatformSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use SensitiveParameter;

class VerifyEmailCode extends Notification
{
    use Queueable;

    public function __construct(#[SensitiveParameter] public string $code) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = app(PlatformSettingsService::class)->current()->display_name;
        $minutes = (int) config('auth.verification.expire', 15);

        return (new MailMessage)
            ->subject('Your '.$appName.' verification code')
            ->greeting('Verify your email')
            ->line('Use this code to verify your email address and finish signing in:')
            ->line(new HtmlString(
                '<div style="text-align:center;font-size:32px;font-weight:bold;letter-spacing:8px;margin:24px 0;">'
                .e($this->code).'</div>'
            ))
            ->line("This code expires in {$minutes} minutes.")
            ->line('If you did not request this code, you can safely ignore this email.');
    }
}
