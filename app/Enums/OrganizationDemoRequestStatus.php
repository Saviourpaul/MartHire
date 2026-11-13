<?php

namespace App\Enums;

enum OrganizationDemoRequestStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case InformationRequested = 'information_requested';
    case GuidedDemoOffered = 'guided_demo_offered';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Duplicate = 'duplicate';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::InformationRequested => 'Information requested',
            self::GuidedDemoOffered => 'Guided demo offered',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Duplicate => 'Duplicate',
        };
    }
}
