<?php

namespace App\Enums;

enum CandidatePipelineStage: string
{
    case Submitted = 'submitted';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Selected = 'selected';
    case Rejected = 'rejected';

    /**
     * @return list<self>
     */
    public function allowedNextStages(): array
    {
        return match ($this) {
            self::Submitted => [self::Shortlisted, self::Rejected],
            self::Shortlisted => [self::Interview, self::Rejected],
            self::Interview => [self::Selected, self::Rejected],
            self::Selected, self::Rejected => [],
        };
    }

    public function canTransitionTo(self $stage): bool
    {
        return in_array($stage, $this->allowedNextStages(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Selected || $this === self::Rejected;
    }

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Shortlisted => 'Shortlisted',
            self::Interview => 'Interview',
            self::Selected => 'Selected',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Submitted => 'bg-gray-100 text-gray-700 dark:bg-white/[0.08] dark:text-gray-300',
            self::Shortlisted => 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-400',
            self::Interview => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
            self::Selected => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
            self::Rejected => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400',
        };
    }
}
