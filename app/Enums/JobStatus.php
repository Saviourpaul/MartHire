<?php

namespace App\Enums;

enum JobStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

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
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
            self::Approved => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
            self::Rejected => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
        };
    }
}
