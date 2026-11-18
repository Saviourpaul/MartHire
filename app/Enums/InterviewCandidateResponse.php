<?php

namespace App\Enums;

enum InterviewCandidateResponse: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Declined = 'declined';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeTone(): StatusBadgeTone
    {
        return match ($this) {
            self::Pending => StatusBadgeTone::Pending,
            self::Confirmed => StatusBadgeTone::Success,
            self::Declined => StatusBadgeTone::Danger,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
