<?php

namespace App\Enums;

enum DashboardPeriod: string
{
    case Today = 'today';
    case ThisWeek = 'this_week';
    case ThisMonth = 'this_month';
    case ThisYear = 'this_year';
    case Custom = 'custom';

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
            self::Today => 'Today',
            self::ThisWeek => 'This Week',
            self::ThisMonth => 'This Month',
            self::ThisYear => 'This Year',
            self::Custom => 'Custom Range',
        };
    }

    public static function tryFromRequest(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::ThisMonth;
    }
}
