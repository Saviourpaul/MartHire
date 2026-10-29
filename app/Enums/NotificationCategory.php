<?php

namespace App\Enums;

use App\Models\User;

enum NotificationCategory: string
{
    case ApplicationStatus = 'application_status';
    case NewApplication = 'new_application';
    case InterviewResponse = 'interview_response';
    case CandidateEmailFailure = 'candidate_email_failure';

    public function label(): string
    {
        return match ($this) {
            self::ApplicationStatus => 'Application status updates',
            self::NewApplication => 'New applications',
            self::InterviewResponse => 'Interview responses',
            self::CandidateEmailFailure => 'Interview email delivery failures',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ApplicationStatus => 'Updates when an employer changes your application status.',
            self::NewApplication => 'New applications for jobs you can access.',
            self::InterviewResponse => 'Candidate confirmations and declines for interviews you can access.',
            self::CandidateEmailFailure => 'Final delivery failures for interview messages you are authorized to manage.',
        };
    }

    /** @return list<self> */
    public static function forUser(User $user): array
    {
        return $user->isApplicant() ? [self::ApplicationStatus]
            : ($user->isOrganization() ? [self::NewApplication, self::InterviewResponse, self::CandidateEmailFailure] : []);
    }

    public function membershipColumn(): string
    {
        return $this->value.'_email';
    }
}
