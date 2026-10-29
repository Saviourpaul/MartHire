<?php

namespace App\Notifications;

use App\Enums\OrganizationRole;
use App\Enums\TeamInvitationStatus;
use App\Models\Scopes\OrganizationScope;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\PlatformSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeamInvitationNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 30;

    public function __construct(
        public readonly int $invitationId,
        public readonly int $organizationId,
        public readonly string $organizationName,
        public readonly OrganizationRole $role,
        private readonly string $token,
    ) {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Join '.$this->organizationName.' on '.app(PlatformSettingsService::class)->current()->display_name)
            ->line('You have been invited to join '.$this->organizationName.' as '.$this->role->label().'.')
            ->action('Review invitation', route('team-invitations.show', $this->token))
            ->line('This invitation expires on '.TeamInvitation::withoutGlobalScope(OrganizationScope::class)->findOrFail($this->invitationId)->expires_at->format('M j, Y, g:i A').'. It can only be used with the invited email address.')
            ->line('Review the invitation to accept or decline it.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Organization team invitation',
            'message' => $this->organizationName.' invited you to join as '.$this->role->label().'. Check your email to review the invitation.',
            'category' => 'team',
            'organization_id' => $this->organizationId,
            'invitation_id' => $this->invitationId,
            'action_url' => route('dashboard'),
        ];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        $invitation = TeamInvitation::withoutGlobalScope(OrganizationScope::class)->find($this->invitationId);

        if (! $invitation || $invitation->status !== TeamInvitationStatus::Pending || ! $invitation->expires_at->isFuture()) {
            return false;
        }

        if ($notifiable instanceof User) {
            return $notifiable->isOrganization() && ! $notifiable->trashed() && ! $notifiable->isSuspended()
                && strcasecmp($notifiable->email, $invitation->email) === 0;
        }

        return $notifiable instanceof AnonymousNotifiable
            && strcasecmp((string) $notifiable->routeNotificationFor('mail'), $invitation->email) === 0;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Team invitation notification failed permanently.', [
            'invitation_id' => $this->invitationId,
            'organization_id' => $this->organizationId,
            'exception' => $exception,
        ]);
    }
}
