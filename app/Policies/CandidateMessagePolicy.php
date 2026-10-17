<?php

namespace App\Policies;

use App\Enums\CandidateMessageStatus;
use App\Enums\InterviewStatus;
use App\Enums\InterviewTemplateType;
use App\Models\CandidateMessage;
use App\Models\User;
use App\Support\OrganizationPermissions;

class CandidateMessagePolicy
{
    public function __construct(private OrganizationPermissions $permissions) {}

    public function retry(User $user, CandidateMessage $message): bool
    {
        if ($message->status !== CandidateMessageStatus::Failed
            || ! $this->permissions->can($user, 'send_candidate_email', application: $message->applicationForm)) {
            return false;
        }

        $interview = $message->interview;

        if (! $interview || $interview->version !== $message->interview_version) {
            return false;
        }

        return $message->type === InterviewTemplateType::InterviewCancel
            ? $interview->status === InterviewStatus::Cancelled
            : in_array($interview->status, [InterviewStatus::Scheduled, InterviewStatus::Rescheduled], true)
                && $interview->end_at->isFuture();
    }
}
