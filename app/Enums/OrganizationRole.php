<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case HrManager = 'hr_manager';
    case Recruiter = 'recruiter';
    case HiringManager = 'hiring_manager';
    case Interviewer = 'interviewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::HrManager => 'HR Manager',
            self::Recruiter => 'Recruiter',
            self::HiringManager => 'Hiring Manager',
            self::Interviewer => 'Interviewer',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
