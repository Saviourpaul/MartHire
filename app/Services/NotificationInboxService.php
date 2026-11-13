<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Enums\OrganizationRole;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\Organization;
use App\Models\OrganizationDemoRequest;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\WorkflowNotification;
use App\Notifications\TeamInvitationNotification;
use App\Support\OrganizationContext;
use App\Support\OrganizationPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NotificationInboxService
{
    public function __construct(private OrganizationContext $context, private OrganizationPermissions $permissions) {}

    public function query(User $user): Builder
    {
        $query = WorkflowNotification::query()->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->id);
        if (! $user->isActive() || ! $user->hasVerifiedEmail() || $user->trashed()) {
            return $query->whereRaw('1 = 0');
        }
        $member = $this->permissions->membership($user);

        return $query->where(function (Builder $visible) use ($user, $member): void {
            $visible->where(function (Builder $account) use ($user): void {
                $account->where('scope', 'account')->where(function (Builder $categories) use ($user): void {
                    if ($user->isApplicant()) {
                        $categories->whereNull('category')
                            ->orWhereNotIn('category', [
                                NotificationCategory::ApplicationStatus->value,
                                NotificationCategory::ApplicantInterviewUpdate->value,
                            ])
                            ->orWhere(function (Builder $applicationNotice) use ($user): void {
                                $applicationNotice->whereIn('category', [
                                    NotificationCategory::ApplicationStatus->value,
                                    NotificationCategory::ApplicantInterviewUpdate->value,
                                ])
                                    ->where('subject_type', 'application')
                                    ->whereIn('subject_id', ApplicationForm::withoutGlobalScope(OrganizationScope::class)->select('id')->where('user_id', $user->id));
                            });
                    } else {
                        $categories->whereNull('category')
                            ->orWhere('category', '!=', NotificationCategory::ApplicationStatus->value);
                    }
                });
            });
            if ($member) {
                $categories = [NotificationCategory::InterviewResponse->value, NotificationCategory::InterviewUpdate->value];
                if ($member->role !== OrganizationRole::Interviewer) {
                    $categories[] = NotificationCategory::NewApplication->value;
                }
                if (in_array($member->role, [OrganizationRole::Owner, OrganizationRole::HrManager, OrganizationRole::Recruiter], true)) {
                    $categories[] = NotificationCategory::CandidateEmailFailure->value;
                    $categories[] = NotificationCategory::CandidateEmailSent->value;
                }
                $visible->orWhere(function (Builder $organization) use ($user, $member, $categories): void {
                    $organization->where('scope', 'organization')->where('organization_id', $member->organization_id)
                        ->where(function (Builder $subjects) use ($user, $member, $categories): void {
                            $subjects->where(function (Builder $applications) use ($user, $categories): void {
                                $applications->whereIn('category', $categories)->where('subject_type', 'application')
                                    ->whereIn('subject_id', $this->permissions->scopeApplications(ApplicationForm::query()->select('id'), $user));
                            })->orWhere(function (Builder $moderation) use ($user, $member): void {
                                $moderation->where('category', NotificationCategory::OrganizationModeration->value)
                                    ->where(function (Builder $moderationSubjects) use ($user, $member): void {
                                        $moderationSubjects->where(function (Builder $jobs) use ($user): void {
                                            $jobs->where('subject_type', 'job')
                                                ->whereIn('subject_id', $this->permissions->scopeJobs(Job::query()->select('id'), $user));
                                        });

                                        if (in_array($member->role, [OrganizationRole::Owner, OrganizationRole::HrManager], true)) {
                                            $moderationSubjects->orWhere(function (Builder $accounts) use ($member): void {
                                                $accounts->where('subject_type', 'user')->whereIn('subject_id',
                                                    OrganizationMember::withoutGlobalScope(OrganizationScope::class)
                                                        ->select('user_id')->where('organization_id', $member->organization_id)
                                                        ->where('status', 'active')->whereNull('deleted_at'));
                                            });
                                        }
                                    });
                            });
                        });
                });
            }
        });
    }

    /** @return array{recentNotifications: Collection, unreadNotificationCount: int} */
    public function summary(User $user): array
    {
        $recent = $this->decorate($this->query($user)->latest()->limit(5)->get(), $user);

        return ['recentNotifications' => $recent, 'unreadNotificationCount' => $this->query($user)->whereNull('read_at')->count()];
    }

    public function unreadCount(User $user): int
    {
        return $this->query($user)->whereNull('read_at')->count();
    }

    public function eligibleForDelivery(WorkflowNotification $notification, User $user): bool
    {
        if ($notification->scope === 'account') {
            return $this->query($user)->whereKey($notification->id)->exists();
        }
        $organization = Organization::find($notification->organization_id);

        return $organization && $this->context->runInOrganization($organization,
            fn (): bool => $this->query($user)->whereKey($notification->id)->exists());
    }

    public function actionUrl(WorkflowNotification $notification, User $user): ?string
    {
        if ($notification->category === NotificationCategory::AdminApplicantAccount->value) {
            return route('admin.users.applicants');
        }

        if ($notification->category === NotificationCategory::AdminDemoRequest->value && $user->isAdmin()) {
            $demoRequest = OrganizationDemoRequest::query()->find($notification->subject_id);

            return $demoRequest
                ? route('admin.demo-requests.show', $demoRequest)
                : route('admin.demo-requests.index');
        }

        if ($notification->category === NotificationCategory::ApplicantAccountStatus->value) {
            return route('login');
        }

        if ($user->isApplicant()
            && in_array($notification->category, [NotificationCategory::ApplicationStatus->value, NotificationCategory::ApplicantInterviewUpdate->value], true)
            && $notification->subject_type === 'application') {
            $application = ApplicationForm::withoutGlobalScope(OrganizationScope::class)
                ->where('user_id', $user->id)->find($notification->subject_id);

            return $application ? route('client.applications.show', $application) : route('client.dashboard');
        }

        if ($notification->scope === 'organization'
            && in_array($notification->category, [
                NotificationCategory::NewApplication->value,
                NotificationCategory::InterviewResponse->value,
                NotificationCategory::InterviewUpdate->value,
                NotificationCategory::CandidateEmailFailure->value,
                NotificationCategory::CandidateEmailSent->value,
            ], true)
            && $notification->subject_type === 'application'
            && $this->permissions->scopeApplications(
                ApplicationForm::query()->whereKey($notification->subject_id),
                $user,
            )->exists()) {
            return route('organisations.candidates.show', $notification->subject_id);
        }

        if ($notification->category === NotificationCategory::OrganizationModeration->value) {
            if ($notification->subject_type === 'user') {
                return route('organisations.team.index');
            }

            if ($notification->subject_type === 'job') {
                $job = Job::query()->find($notification->subject_id);

                return $job ? route('organisations.jobs.show', $job) : route('dashboard');
            }
        }

        return route('dashboard');
    }

    /** @param Collection<int, WorkflowNotification> $notifications
     * @return Collection<int, array<string, mixed>>
     */
    public function items(Collection $notifications, User $user): Collection
    {
        return $this->decorate($notifications, $user)->map(fn (WorkflowNotification $notification): array => $this->item($notification, $user));
    }

    /** @param Collection<int, WorkflowNotification> $notifications
     * @return Collection<int, WorkflowNotification>
     */
    private function decorate(Collection $notifications, User $user): Collection
    {
        $ids = $notifications->where('type', TeamInvitationNotification::class)
            ->map(fn (WorkflowNotification $notification): ?int => isset($notification->data['invitation_id']) ? (int) $notification->data['invitation_id'] : null)
            ->filter()->all();
        $invitations = $ids === [] ? collect() : TeamInvitation::withoutGlobalScope(OrganizationScope::class)
            ->whereIn('id', $ids)->get(['id', 'status', 'expires_at'])->keyBy('id');

        return $notifications->map(function (WorkflowNotification $notification) use ($user, $invitations): WorkflowNotification {
            $data = $notification->data;
            $invitation = $invitations->get($data['invitation_id'] ?? null);
            if ($notification->type === TeamInvitationNotification::class && $invitation) {
                $status = $invitation->status->value === 'pending' && $invitation->expires_at->isPast() ? 'expired' : $invitation->status->value;
                if ($status !== 'pending') {
                    $data['message'] = 'This team invitation is '.$status.'.';
                }
            }
            $notification->data = [...$data, 'action_url' => $this->actionUrl($notification, $user)];

            return $notification;
        });
    }

    /** @return array<string, mixed> */
    public function item(WorkflowNotification $notification, User $user): array
    {
        return [
            'id' => $notification->id, 'title' => $notification->data['title'] ?? 'Notification',
            'message' => $notification->data['message'] ?? '', 'created_at' => $notification->created_at,
            'read_at' => $notification->read_at, 'action_url' => $this->actionUrl($notification, $user),
        ];
    }
}
