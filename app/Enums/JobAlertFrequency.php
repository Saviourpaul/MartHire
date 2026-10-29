<?php

namespace App\Enums;

enum JobAlertFrequency: string
{
    case Off = 'off';
    case Daily = 'daily';
    case Weekly = 'weekly';

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
            self::Off => 'Off',
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
        };
    }
}
