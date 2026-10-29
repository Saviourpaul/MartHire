<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Enums\OrganizationRole;
use App\Jobs\SendWorkflowNotificationEmail;
use App\Models\ApplicationForm;
use App\Models\CandidateMessage;
use App\Models\Interview;
use App\Models\NotificationEmailOutbox;
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
    public function __construct(private NotificationPreferenceService $preferences) {}

    public function applicationSubmitted(ApplicationForm $application): void
    {
        $this->publish($application, NotificationCategory::NewApplication, 'application-submitted:'.$application->id,
            $this->employerRecipients($application, false), 'New application',
            'A new application was submitted for '.$application->job->title.' ('.$application->reference.').');
    }

    public function applicationStatusChanged(ApplicationForm $application, int $historyId): void
    {
        $candidate = User::query()->active()->whereNotNull('email_verified_at')->where('role', 'applicant')->whereKey($application->user_id)->get();
        $this->publish($application, NotificationCategory::ApplicationStatus, 'application-status:'.$historyId,
            $candidate, 'Application status updated',
            'Your application for '.$application->job->title.' is now '.$application->status->label().'.');
    }

    public function interviewResponded(Interview $interview, int $activityId): void
    {
        $application = $interview->applicationForm;
        $this->publish($application, NotificationCategory::InterviewResponse, 'interview-response:'.$activityId,
            $this->employerRecipients($application, true), 'Interview response',
            'The candidate '.$interview->candidate_response->value.' the interview for '.$application->job->title.' ('.$application->reference.').');
    }

    public function candidateEmailFailed(CandidateMessage $message, int $activityId): void
    {
        $application = $message->applicationForm;
        $eligible = $this->employerRecipients($application, false, true);
        $sender = $eligible->where('id', $message->sent_by);
        $recipients = $sender->isNotEmpty() ? $sender : $eligible->filter(fn (User $user): bool => in_array($user->membership_role, ['owner', 'hr_manager'], true));
        $this->publish($application, NotificationCategory::CandidateEmailFailure, 'candidate-email-failed:'.$activityId,
            $recipients, 'Interview email delivery failed',
            'An interview email for '.$application->job->title.' ('.$application->reference.') could not be delivered. Open the application to review and retry.');
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

    /** @param Collection<int, User> $recipients */
    private function publish(ApplicationForm $application, NotificationCategory $category, string $eventKey, Collection $recipients, string $title, string $message): void
    {
        $organizationId = $category === NotificationCategory::ApplicationStatus ? null : $application->organization_id;
        $enabled = $this->preferences->enabledRecipients($recipients->pluck('id')->all(), $category, $organizationId);
        foreach ($recipients as $recipient) {
            $notification = WorkflowNotification::query()->firstOrCreate([
                'notifiable_type' => $recipient->getMorphClass(), 'notifiable_id' => $recipient->id, 'event_key' => $eventKey,
            ], [
                'id' => (string) Str::uuid(), 'type' => self::class,
                'scope' => $organizationId === null ? 'account' : 'organization', 'organization_id' => $organizationId,
                'category' => $category->value, 'subject_type' => 'application', 'subject_id' => $application->id, 'schema_version' => 1,
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
