<?php

namespace App\Enums;

use App\Models\User;

enum NotificationCategory: string
{
    case ApplicationStatus = 'application_status';
    case NewApplication = 'new_application';
    case InterviewResponse = 'interview_response';
    case CandidateEmailFailure = 'candidate_email_failure';
    case CandidateEmailSent = 'candidate_email_sent';
    case InterviewUpdate = 'interview_update';
    case ApplicantInterviewUpdate = 'applicant_interview_update';
    case OrganizationModeration = 'organization_moderation';
    case AdminApplicantAccount = 'admin_applicant_account';
    case AdminDemoRequest = 'admin_demo_request';
    case ApplicantAccountStatus = 'applicant_account_status';

    public function label(): string
    {
        return match ($this) {
            self::ApplicationStatus => 'Application status updates',
            self::NewApplication => 'New applications',
            self::InterviewResponse => 'Interview responses',
            self::CandidateEmailFailure => 'Interview email delivery failures',
            self::CandidateEmailSent => 'Interview email delivery confirmations',
            self::InterviewUpdate => 'Interview updates',
            self::ApplicantInterviewUpdate => 'Interview updates',
            self::OrganizationModeration => 'Organization moderation updates',
            self::AdminApplicantAccount => 'Applicant account updates',
            self::AdminDemoRequest => 'Organization demo requests',
            self::ApplicantAccountStatus => 'Applicant account status',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ApplicationStatus => 'Updates when an employer changes your application status.',
            self::NewApplication => 'New applications for jobs you can access.',
            self::InterviewResponse => 'Candidate confirmations and declines for interviews you can access.',
            self::CandidateEmailFailure => 'Final delivery failures for interview messages you are authorized to manage.',
            self::CandidateEmailSent => 'Confirmation when an interview email is delivered to a candidate.',
            self::InterviewUpdate => 'Interview schedule, response, and completion updates for candidates you can access.',
            self::ApplicantInterviewUpdate => 'Updates about interviews for your applications.',
            self::OrganizationModeration => 'Platform administration changes affecting your organization or its job postings.',
            self::AdminApplicantAccount => 'Applicant registrations, profile updates, and moderation changes.',
            self::AdminDemoRequest => 'New organization demo requests requiring administrative review.',
            self::ApplicantAccountStatus => 'Important changes to your Marthire account access.',
        };
    }

    /** @return list<self> */
    public static function forUser(User $user): array
    {
        return $user->isAdmin() ? [self::AdminApplicantAccount, self::AdminDemoRequest]
            : ($user->isApplicant() ? [self::ApplicationStatus]
                : ($user->isOrganization() ? [self::NewApplication, self::InterviewResponse, self::CandidateEmailFailure, self::OrganizationModeration] : []));
    }

    public function membershipColumn(): string
    {
        return $this->value.'_email';
    }
}
