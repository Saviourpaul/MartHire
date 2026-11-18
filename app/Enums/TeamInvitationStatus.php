<?php

namespace App\Enums;

enum TeamInvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeTone(): StatusBadgeTone
    {
        return match ($this) {
            self::Pending => StatusBadgeTone::Pending,
            self::Accepted => StatusBadgeTone::Success,
            self::Declined => StatusBadgeTone::Danger,
            self::Revoked => StatusBadgeTone::Neutral,
            self::Expired => StatusBadgeTone::Danger,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
