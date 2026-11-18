<?php

namespace App\Enums;

enum InterviewStatus: string
{
    case Scheduled = 'scheduled';
    case Rescheduled = 'rescheduled';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Scheduled, self::Rescheduled], true);
    }

    public function badgeTone(): StatusBadgeTone
    {
        return match ($this) {
            self::Scheduled, self::Rescheduled => StatusBadgeTone::Processing,
            self::Cancelled => StatusBadgeTone::Danger,
            self::Completed => StatusBadgeTone::Success,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
