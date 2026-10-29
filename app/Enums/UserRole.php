<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Organization = 'organization';
    case Applicant = 'applicant';

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
            self::Admin => 'Admin',
            self::Organization => 'Organization',
            self::Applicant => 'Applicant',
        };
    }
}
