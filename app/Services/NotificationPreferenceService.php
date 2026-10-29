<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Models\NotificationEmailOutbox;
use App\Models\NotificationPreference;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\OrganizationMemberPreference;
use App\Models\Scopes\OrganizationScope;
use App\Models\SettingsAuditEvent;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\DB;

class NotificationPreferenceService
{
    public function __construct(private OrganizationContext $context) {}

    /** @return array<string, mixed> */
    public function form(User $user): array
    {
        $categories = NotificationCategory::forUser($user);
        $saved = NotificationPreference::query()->where('user_id', $user->id)->pluck('email_enabled', 'category');
        $member = $user->isOrganization() ? OrganizationMember::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->context->organizationId())->where('user_id', $user->id)->where('status', 'active')->first() : null;
        $override = $member ? OrganizationMemberPreference::query()->where('organization_member_id', $member->id)->first() : null;
        $personal = [];
        $membership = [];
        foreach ($categories as $category) {
            $personal[$category->value] = (bool) ($saved[$category->value] ?? false);
            $membership[$category->value] = $override?->getAttribute($category->membershipColumn());
        }

        return [
            'categories' => array_map(fn (NotificationCategory $category): array => [
                'value' => $category->value, 'label' => $category->label(), 'description' => $category->description(),
            ], $categories),
            'emailPreferences' => $personal,
            'membershipEmailPreferences' => $membership,
            'notificationOrganization' => $member ? $this->context->organization() : null,
        ];
    }

    /** @param array<string, bool|int|string> $preferences */
    public function update(User $user, array $preferences): void
    {
        DB::transaction(function () use ($user, $preferences): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $allowed = NotificationCategory::forUser($user);
            foreach ($allowed as $category) {
                if (array_key_exists($category->value, $preferences)) {
                    NotificationPreference::query()->updateOrCreate(
                        ['user_id' => $user->id, 'category' => $category->value],
                        ['email_enabled' => (bool) $preferences[$category->value]],
                    );
                }
            }
            SettingsAuditEvent::create([
                'actor_id' => $user->id, 'user_id' => $user->id, 'scope' => 'personal',
                'action' => 'notification_preferences_updated', 'changes' => $preferences, 'created_at' => now(),
            ]);
            $this->cancelDisabled($user);
        });
    }

    /** @param array<string, string> $preferences */
    public function updateMembership(User $user, Organization $organization, array $preferences): void
    {
        DB::transaction(function () use ($user, $organization, $preferences): void {
            Organization::query()->whereKey($organization->id)->lockForUpdate()->firstOrFail();
            abort_unless($this->context->organizationId() === $organization->id, 409, 'Your active organization changed. Reload Settings before saving.');
            $member = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
                ->where('organization_id', $organization->id)->where('user_id', $user->id)->where('status', 'active')->lockForUpdate()->firstOrFail();
            abort_unless($user->isOrganization() && $user->isActive() && $user->hasVerifiedEmail(), 403);
            $values = [];
            foreach (NotificationCategory::forUser($user) as $category) {
                if (array_key_exists($category->value, $preferences)) {
                    $values[$category->membershipColumn()] = match ($preferences[$category->value]) {
                        'on' => true, 'off' => false, default => null,
                    };
                }
            }
            OrganizationMemberPreference::query()->updateOrCreate(['organization_member_id' => $member->id], $values);
            SettingsAuditEvent::create([
                'actor_id' => $user->id, 'user_id' => $user->id, 'scope' => 'personal',
                'action' => 'membership_notification_preferences_updated',
                'changes' => ['organization_id' => $organization->id, ...$values], 'created_at' => now(),
            ]);
            $this->cancelDisabled($user);
        });
    }

    public function emailEnabled(User $user, NotificationCategory $category, ?int $organizationId = null): bool
    {
        return $user->isActive() && $user->hasVerifiedEmail() && ! $user->trashed()
            && in_array($category, NotificationCategory::forUser($user), true)
            && in_array($user->id, $this->enabledRecipients([$user->id], $category, $organizationId), true);
    }

    /** @param list<int> $userIds
     * @return list<int>
     */
    public function enabledRecipients(array $userIds, NotificationCategory $category, ?int $organizationId): array
    {
        $personal = NotificationPreference::query()->whereIn('user_id', $userIds)
            ->where('category', $category->value)->pluck('email_enabled', 'user_id');
        $overrides = collect();
        if ($organizationId !== null && $category !== NotificationCategory::ApplicationStatus) {
            $overrides = DB::table('organization_members')->leftJoin('organization_member_preferences', 'organization_members.id', '=', 'organization_member_preferences.organization_member_id')
                ->where('organization_members.organization_id', $organizationId)->whereIn('organization_members.user_id', $userIds)
                ->where('organization_members.status', 'active')->whereNull('organization_members.deleted_at')
                ->pluck('organization_member_preferences.'.$category->membershipColumn(), 'organization_members.user_id');
        }

        return array_values(array_filter($userIds, fn (int $id): bool => (bool) ($overrides[$id] ?? $personal[$id] ?? false)));
    }

    private function cancelDisabled(User $user): void
    {
        NotificationEmailOutbox::query()->whereIn('status', ['pending', 'processing'])
            ->whereHas('notification', fn ($query) => $query->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->id))
            ->with('notification')->chunkById(100, function ($entries) use ($user): void {
                foreach ($entries as $entry) {
                    $category = NotificationCategory::tryFrom((string) $entry->notification?->category);
                    if (! $category || ! $this->emailEnabled($user, $category, $entry->notification->organization_id)) {
                        NotificationEmailOutbox::query()->whereKey($entry->id)->whereIn('status', ['pending', 'processing'])
                            ->update(['status' => 'cancelled', 'locked_until' => null, 'last_error' => null]);
                    }
                }
            });
    }
}
