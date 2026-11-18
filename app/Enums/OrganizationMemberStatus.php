<?php

namespace App\Enums;

enum OrganizationMemberStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeTone(): StatusBadgeTone
    {
        return match ($this) {
            self::Active => StatusBadgeTone::Success,
            self::Suspended => StatusBadgeTone::Danger,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
