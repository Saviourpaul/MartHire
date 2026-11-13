<?php

namespace App\Enums;

enum OrganizationDemoOnboardingStatus: string
{
    case NotStarted = 'not_started';
    case InvitationSent = 'invitation_sent';
    case InvitationFailed = 'invitation_failed';
    case InvitationExpired = 'invitation_expired';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::InvitationSent => 'Invitation sent',
            self::InvitationFailed => 'Invitation failed',
            self::InvitationExpired => 'Invitation expired',
            self::InProgress => 'Onboarding in progress',
            self::Completed => 'Completed',
        };
    }
}
