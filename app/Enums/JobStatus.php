<?php

namespace App\Enums;

enum JobStatus: string
{
    case Open = 'open';
    case Suspended = 'suspended';
    case Closed = 'closed';

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
            self::Open => 'Open',
            self::Suspended => 'Suspended',
            self::Closed => 'Closed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
            self::Suspended => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
            self::Closed => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        };
    }
}
