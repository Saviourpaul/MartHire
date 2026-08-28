<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
            self::Suspended => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
        };
    }
}
