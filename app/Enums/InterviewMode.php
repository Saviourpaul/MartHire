<?php

namespace App\Enums;

enum InterviewMode: string
{
    case Online = 'online';
    case Onsite = 'onsite';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Onsite => 'Onsite',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
