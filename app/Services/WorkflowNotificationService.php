<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Jobs\SendWorkflowNotificationEmail;
use App\Models\ApplicationForm;
use App\Models\CandidateMessage;
use App\Models\Interview;
use App\Models\Job;
use App\Models\JobPostMember;
use App\Models\NotificationEmailOutbox;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkflowNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class WorkflowNotificationService
{
    public function __construct(
        private NotificationPreferenceService $preferences,
        private WorkspaceUpdateBroadcaster $workspaceUpdates,
    ) {}

    public function applicationSubmitted(ApplicationForm $application): void
    {
        $this->publish(
            NotificationCategory::NewApplication, 'application-submitted:'.$application->id,
            $this->employerRecipients($application, false), 'organization', $application->organization_id,
            'application', $application->id, 'New application',
            'A new application was submitted for '.$application->job->title.' ('.$application->reference.').'
        );
        $this->workspaceUpdates->organization(
            (int) $application->organization_id,
            ['applications', 'candidates', 'notifications'],
            'A new application was submitted.',
            'info',
        );
    }

    public function applicationStatusChanged(ApplicationForm $application, int $historyId): void
    {
        $candidate = User::query()->active()->whereNotNull('email_verified_at')->where('role', 'applicant')->whereKey($application->user_id)->get();
        $this->publish(
            NotificationCategory::ApplicationStatus, 'application-status:'.$historyId,
            $candidate, 'account', null, 'application', $application->id,
            'Application status updated',
            'Your application for '.$application->job->title.' is now '.$application->status->label().'.'
        );
        $this->workspaceUpdates->organization(
            (int) $application->organization_id,
            ['applications', 'candidates', 'interviews', 'notifications'],
        );
        $this->workspaceUpdates->applicant(
            (int) $application->user_id,
            ['applications', 'interviews', 'notifications'],
            'Your application moved to '.$application->status->label().'.',
            'success',
        );
    }

    public function interviewResponded(Interview $interview, int $activityId): void
    {
        $application = $interview->applicationForm;
        $this->publish(
            NotificationCategory::InterviewResponse, 'interview-response:'.$activityId,
            $this->employerRecipients($application, true), 'organization', $application->organization_id,
            'application', $application->id, 'Interview response',
            'The candidate '.$interview->candidate_response->value.' the interview for '.$application->job->title.' ('.$application->reference.').'
        );
        $responseLabel = $interview->candidate_response->label();
        $this->publish(
            NotificationCategory::ApplicantInterviewUpdate, 'interview-response-applicant:'.$activityId,
            $this->applicantRecipients($application), 'account', null,
            'application', $application->id, 'Interview response recorded',
            'Your '.$responseLabel.' response for the interview for '.$application->job->title.' was recorded.',
        );
        $this->workspaceUpdates->organization(
            (int) $application->organization_id,
            ['applications', 'candidates', 'interviews', 'notifications'],
            'The candidate '.$responseLabel.' the interview invitation.',
            $interview->candidate_response->value === 'declined' ? 'warning' : 'success',
        );
        $this->workspaceUpdates->applicant(
            (int) $application->user_id,
            ['applications', 'interviews', 'notifications'],
            null,
            'info',
        );
    }

    public function interviewLifecycleChanged(Interview $interview, int $activityId, string $action): void
    {
        if ($activityId < 1) {
            return;
        }

        $application = $interview->applicationForm;
        $application->loadMissing('job');
        [$label, $toastType] = match ($action) {
            'scheduled' => ['scheduled', 'success'],
            'rescheduled' => ['rescheduled', 'info'],
            'cancelled' => ['cancelled', 'warning'],
            'completed' => ['marked completed', 'success'],
            default => ['updated', 'info'],
        };
        $eventKey = 'interview-'.$action.':'.$activityId;
        $jobTitle = (string) ($application->job?->title ?? 'your application');

        $this->publish(
            NotificationCategory::InterviewUpdate, $eventKey,
            $this->employerRecipients($application, true), 'organization', $application->organization_id,
            'application', $application->id, 'Interview '.$label,
            'An interview for '.$jobTitle.' was '.$label.'.',
        );
        $this->publish(
            NotificationCategory::ApplicantInterviewUpdate, $eventKey,
            $this->applicantRecipients($application), 'account', null,
            'application', $application->id, 'Interview '.$label,
            'Your interview for '.$jobTitle.' was '.$label.'.',
        );
        $this->workspaceUpdates->organization(
            (int) $application->organization_id,
            ['applications', 'candidates', 'interviews', 'notifications'],
            null,
            'info',
        );
        $this->workspaceUpdates->applicant(
            (int) $application->user_id,
            ['applications', 'interviews', 'notifications'],
            'Your interview for '.$jobTitle.' was '.$label.'.',
            $toastType,
        );
    }

    public function candidateEmailFailed(CandidateMessage $message, int $activityId): void
    {
        $application = $message->applicationForm;
        $eligible = $this->employerRecipients($application, false, true);
        $sender = $eligible->where('id', $message->sent_by);
        $recipients = $sender->isNotEmpty() ? $sender : $eligible->filter(fn (User $user): bool => in_array($user->membership_role, ['owner', 'hr_manager'], true));
        $this->publish(
            NotificationCategory::CandidateEmailFailure, 'candidate-email-failed:'.$activityId,
            $recipients, 'organization', $application->organization_id,
            'application', $application->id, 'Interview email delivery failed',
            'An interview email for '.$application->job->title.' ('.$application->reference.') could not be delivered. Open the application to review and retry.'
        );
        $this->workspaceUpdates->organization(
            (int) $application->organization_id,
            ['applications', 'candidates', 'interviews', 'notifications'],
            'Interview email delivery failed.',
            'error',
        );
    }

    public function candidateEmailSent(CandidateMessage $message, int $activityId): void
    {
        if ($activityId < 1) {
            return;
        }

        $application = $message->applicationForm;
        $application->loadMissing('job');
        $this->publish(
            NotificationCategory::CandidateEmailSent, 'candidate-email-sent:'.$activityId,
            $this->employerRecipients($application, false, true), 'organization', $application->organization_id,
            'application', $application->id, 'Interview email sent',
            'The interview email for '.$application->job->title.' ('.$application->reference.') was sent successfully.',
        );
        $this->workspaceUpdates->organization(
            (int) $application->organization_id,
            ['applications', 'candidates', 'interviews', 'notifications'],
            'Interview email sent successfully.',
            'success',
        );
    }

    /** @return Collection<int, User> */
    private function applicantRecipients(ApplicationForm $application): Collection
    {
        return User::query()->active()->whereNotNull('email_verified_at')
            ->where('role', UserRole::Applicant->value)->whereKey($application->user_id)->get();
    }

    public function organizationJobSuspended(Job $job): void
    {
        $recipients = $this->organizationJobRecipients($job);

        $this->publish(
            NotificationCategory::OrganizationModeration, 'organization-job-suspended:'.$job->id,
            $recipients, 'organization', $job->organization_id,
            'job', $job->id, 'Job posting suspended',
            'The posting "'.$job->title.'" was suspended by platform administration.'
        );
    }

    public function organizationAccountAccessChanged(Organization $organization, User $account, int $auditId, string $change): void
    {
        $recipients = User::query()->select('users.*', 'organization_members.role as membership_role')
            ->join('organization_members', 'organization_members.user_id', '=', 'users.id')
            ->where('users.role', 'organization')->where('users.status', 'active')
            ->whereNotNull('users.email_verified_at')->whereNull('users.deleted_at')
            ->where('organization_members.organization_id', $organization->id)
            ->where('organization_members.status', 'active')->whereNull('organization_members.deleted_at')
            ->whereIn('organization_members.role', [OrganizationRole::Owner->value, OrganizationRole::HrManager->value])
            ->where('users.id', '<>', $account->id)
            ->get();

        $message = match ($change) {
            'suspended' => 'An organization team member account was suspended by platform administration.',
            'activated' => 'An organization team member account was activated by platform administration.',
            'removed' => 'An organization team member account was removed by platform administration.',
            default => 'An organization team member account was updated by platform administration.',
        };

        $this->publish(
            NotificationCategory::OrganizationModeration,
            'organization-account-moderated:'.$organization->id.':'.$auditId,
            $recipients, 'organization', $organization->id,
            'user', $account->id, 'Organization account update', $message
        );
    }

    public function applicantRegistered(User $applicant): void
    {
        if (! $applicant->isApplicant()) {
            return;
        }

        $this->publish(
            NotificationCategory::AdminApplicantAccount, 'applicant-registered:'.$applicant->id,
            $this->adminRecipients(), 'account', null, 'user', $applicant->id,
            'New applicant registered', 'A new applicant has registered and may need account review.',
        );
    }

    /** The audit event ID gives profile notices a stable, retry-safe event key. */
    public function applicantProfileUpdated(User $applicant, int $auditId): void
    {
        if (! $applicant->isApplicant() || $auditId < 1) {
            return;
        }

        $this->publish(
            NotificationCategory::AdminApplicantAccount, 'applicant-profile-updated:'.$auditId,
            $this->adminRecipients(), 'account', null, 'user', $applicant->id,
            'Applicant account details updated', 'An applicant updated their account details.',
        );
    }

    public function applicantAccountStatusChanged(User $actor, User $applicant, int $auditId, UserStatus $status): void
    {
        if (! $applicant->isApplicant() || $auditId < 1) {
            return;
        }

        $adminMessage = 'An applicant account status was changed to '.$status->label().'.';
        $this->publish(
            NotificationCategory::AdminApplicantAccount, 'applicant-account-moderated:'.$auditId,
            $this->adminRecipients($actor->id), 'account', null, 'user', $applicant->id,
            'Applicant account status changed', $adminMessage,
        );

        $applicantMessage = match ($status) {
            UserStatus::Active => 'Your Marthire account is active. Sign in to continue.',
            UserStatus::Suspended => 'Your Marthire account has been suspended. Please contact support if you need help.',
            UserStatus::Pending => 'Your Marthire account is pending review. We will update you when its status changes.',
        };

        $this->publish(
            NotificationCategory::ApplicantAccountStatus, 'applicant-account-status:'.$auditId,
            collect([$applicant]), 'account', null, 'user', $applicant->id,
            'Account status updated', $applicantMessage, forceEmail: true,
        );

        $this->workspaceUpdates->applicant(
            (int) $applicant->id,
            ['notifications'],
            $applicantMessage,
            match ($status) {
                UserStatus::Active => 'success',
                UserStatus::Suspended => 'warning',
                UserStatus::Pending => 'info',
            },
        );

    }

    /** @return Collection<int, User> */
    private function adminRecipients(?int $exceptUserId = null): Collection
    {
        return User::query()->role(UserRole::Admin)->active()->whereNotNull('email_verified_at')
            ->when($exceptUserId, fn (Builder $query) => $query->where('users.id', '<>', $exceptUserId))
            ->get();
    }

    /** @return Collection<int, User> */
    private function employerRecipients(ApplicationForm $application, bool $includeInterviewers, bool $sendersOnly = false): Collection
    {
        $roles = $sendersOnly ? ['owner', 'hr_manager', 'recruiter'] : ['owner', 'hr_manager', 'recruiter', 'hiring_manager'];
        if ($includeInterviewers) {
            $roles[] = 'interviewer';
        }

        return User::query()->select('users.*', 'organization_members.role as membership_role')
            ->join('organization_members', 'organization_members.user_id', '=', 'users.id')
            ->where('users.role', 'organization')->where('users.status', 'active')->whereNotNull('users.email_verified_at')
            ->where('organization_members.organization_id', $application->organization_id)
            ->where('organization_members.status', 'active')->whereNull('organization_members.deleted_at')
            ->whereIn('organization_members.role', $roles)->where(function (Builder $query) use ($application): void {
                $query->whereIn('organization_members.role', [OrganizationRole::Owner->value, OrganizationRole::HrManager->value])
                    ->orWhereIn('users.id', DB::table('job_post_members')->select('user_id')
                        ->where('organization_id', $application->organization_id)->where('job_id', $application->job_id));
            })->get();
    }

    /** @return Collection<int, User> */
    private function organizationJobRecipients(Job $job): Collection
    {
        return User::query()->select('users.*', 'organization_members.role as membership_role')
            ->join('organization_members', 'organization_members.user_id', '=', 'users.id')
            ->where('users.role', 'organization')->where('users.status', 'active')
            ->whereNotNull('users.email_verified_at')->whereNull('users.deleted_at')
            ->where('organization_members.organization_id', $job->organization_id)
            ->where('organization_members.status', 'active')->whereNull('organization_members.deleted_at')
            ->whereIn('organization_members.role', [
                OrganizationRole::Owner->value, OrganizationRole::HrManager->value,
                OrganizationRole::Recruiter->value, OrganizationRole::HiringManager->value,
            ])
            ->where(function (Builder $query) use ($job): void {
                $query->whereIn('organization_members.role', [OrganizationRole::Owner->value, OrganizationRole::HrManager->value])
                    ->orWhereIn('users.id', JobPostMember::query()->select('user_id')
                        ->where('organization_id', $job->organization_id)->where('job_id', $job->id));
            })->get();
    }

    /** @param Collection<int, User> $recipients */
    private function publish(
        NotificationCategory $category,
        string $eventKey,
        Collection $recipients,
        string $scope,
        ?int $organizationId,
        string $subjectType,
        int $subjectId,
        string $title,
        string $message,
        bool $forceEmail = false,
    ): void {
        $emailRecipients = $recipients->filter(fn (User $recipient): bool => in_array($category, NotificationCategory::forUser($recipient), true));
        $enabled = $forceEmail
            ? $recipients->filter(fn (User $recipient): bool => $recipient->hasVerifiedEmail())->pluck('id')->all()
            : ($emailRecipients->isEmpty() ? [] : $this->preferences->enabledRecipients($emailRecipients->pluck('id')->all(), $category, $organizationId));
        foreach ($recipients as $recipient) {
            $notification = WorkflowNotification::query()->firstOrCreate([
                'notifiable_type' => $recipient->getMorphClass(), 'notifiable_id' => $recipient->id, 'event_key' => $eventKey,
            ], [
                'id' => (string) Str::uuid(), 'type' => self::class,
                'scope' => $scope, 'organization_id' => $organizationId,
                'category' => $category->value, 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'schema_version' => 1,
                'data' => ['title' => $title, 'message' => $message, 'category' => $category->value],
            ]);
            if ($notification->wasRecentlyCreated && in_array($recipient->id, $enabled, true)) {
                $entry = NotificationEmailOutbox::create(['notification_id' => $notification->id, 'available_at' => now()]);
                DB::afterCommit(fn () => self::wake($entry->id));
            }
        }

    }

    public static function wake(int $id): void
    {
        try {
            $pending = dispatch(new SendWorkflowNotificationEmail($id));
            unset($pending);
        } catch (Throwable) {
            Log::warning('Workflow notification queue unavailable; the outbox will retry.', ['outbox_id' => $id]);
        }
    }
}
