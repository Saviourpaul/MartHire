<?php

namespace App\Enums;

enum JobStatus: string
{
    case Open = 'open';
    case Suspended = 'suspended';
    case Closed = 'closed';
    case Draft = 'draft';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Statuses an admin can see/assign in moderation surfaces. Draft is an
     * org-private authoring state and is deliberately excluded.
     *
     * @return list<self>
     */
    public static function moderatableCases(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status): bool => $status !== self::Draft));
    }

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Suspended => 'Suspended',
            self::Closed => 'Closed',
            self::Draft => 'Draft',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
            self::Suspended => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
            self::Closed => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            self::Draft => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
        };
    }
}
