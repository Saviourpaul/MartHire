<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Pending = 'pending';

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
            self::Pending => 'Pending',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
            self::Suspended => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
            self::Pending => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
        };
    }
}
