<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Enums\OrganizationRole;
use App\Models\ApplicationForm;
use App\Models\Organization;
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
                    $categories->whereNull('category')->orWhere('category', '!=', NotificationCategory::ApplicationStatus->value);
                    if ($user->isApplicant()) {
                        $categories->orWhere(function (Builder $status) use ($user): void {
                            $status->where('category', NotificationCategory::ApplicationStatus->value)
                                ->where('subject_type', 'application')
                                ->whereIn('subject_id', ApplicationForm::withoutGlobalScope(OrganizationScope::class)->select('id')->where('user_id', $user->id));
                        });
                    }
                });
            });
            if ($member) {
                $categories = [NotificationCategory::InterviewResponse->value];
                if ($member->role !== OrganizationRole::Interviewer) {
                    $categories[] = NotificationCategory::NewApplication->value;
                }
                if (in_array($member->role, [OrganizationRole::Owner, OrganizationRole::HrManager, OrganizationRole::Recruiter], true)) {
                    $categories[] = NotificationCategory::CandidateEmailFailure->value;
                }
                $visible->orWhere(function (Builder $organization) use ($user, $member, $categories): void {
                    $organization->where('scope', 'organization')->where('organization_id', $member->organization_id)
                        ->whereIn('category', $categories)->where('subject_type', 'application')
                        ->whereIn('subject_id', $this->permissions->scopeApplications(ApplicationForm::query()->select('id'), $user));
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
        if ($notification->subject_type === 'application' && $notification->subject_id) {
            return route($user->isApplicant() ? 'client.applications.show' : 'employer.applications.show', $notification->subject_id);
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
