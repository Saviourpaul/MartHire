<?php

namespace App\Enums;

enum InterviewTemplateType: string
{
    case InterviewOnline = 'interview_online';
    case InterviewOnsite = 'interview_onsite';
    case InterviewReschedule = 'interview_reschedule';
    case InterviewCancel = 'interview_cancel';

    public function label(): string
    {
        return match ($this) {
            self::InterviewOnline => 'Online interview invitation',
            self::InterviewOnsite => 'Onsite interview invitation',
            self::InterviewReschedule => 'Interview rescheduled',
            self::InterviewCancel => 'Interview cancelled',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
