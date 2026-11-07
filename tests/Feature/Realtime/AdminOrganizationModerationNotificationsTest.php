<?php

use App\Enums\JobStatus;
use App\Enums\NotificationCategory;
use App\Enums\OrganizationRole;
use App\Enums\UserStatus;
use App\Models\JobPostMember;
use App\Models\OrganizationMember;
use App\Models\OrganizationMemberPreference;
use App\Models\User;
use App\Models\WorkflowNotification;
use App\Support\OrganizationContext;

test('admin job suspension creates organization-scoped inbox notices for authorized job members', function () {
    [$organization, $owner] = organizationWithOwner();
    $assigned = addOrganizationMember($organization, OrganizationRole::HiringManager);
    $unassigned = addOrganizationMember($organization, OrganizationRole::HiringManager);
    $job = app(OrganizationContext::class)->runInOrganization($organization, fn () => $owner->jobs()->create([
        'organization_id' => $organization->id,
        'title' => 'Platform Reliability Engineer',
        'description' => '<p>Build reliable systems.</p>',
        'company' => 'Example Org',
        'employment_type' => 'full_time',
        'start_date' => today(),
        'due_date' => today()->addDays(30),
        'status' => JobStatus::Open,
    ]));
    app(OrganizationContext::class)->runInOrganization($organization, fn () => JobPostMember::create([
        'organization_id' => $organization->id,
        'job_id' => $job->id,
        'user_id' => $assigned->user_id,
    ]));
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.jobs.suspend', $job), [
        'suspension_reason' => 'Posting needs review.',
    ])->assertRedirect();

    expect($job->fresh()->status)->toBe(JobStatus::Suspended);
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $owner->id,
        'scope' => 'organization',
        'organization_id' => $organization->id,
        'category' => NotificationCategory::OrganizationModeration->value,
        'subject_type' => 'job',
        'subject_id' => $job->id,
    ]);
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $assigned->user_id,
        'scope' => 'organization',
        'organization_id' => $organization->id,
        'category' => NotificationCategory::OrganizationModeration->value,
        'subject_type' => 'job',
        'subject_id' => $job->id,
    ]);
    $this->assertDatabaseMissing('notifications', [
        'notifiable_id' => $unassigned->user_id,
        'category' => NotificationCategory::OrganizationModeration->value,
        'subject_type' => 'job',
        'subject_id' => $job->id,
    ]);

    $this->actingAs($assigned->user)->get(route('notifications.index'))
        ->assertOk()->assertSee('Job posting suspended')->assertSee('suspended by platform administration');
});

test('admin organization-account suspension notifies only the other organization managers', function () {
    [$organization, $owner] = organizationWithOwner();
    $manager = addOrganizationMember($organization, OrganizationRole::HrManager);
    $target = addOrganizationMember($organization, OrganizationRole::Recruiter);
    [$otherOrganization] = organizationWithOwner();
    app(OrganizationContext::class)->runInOrganization($otherOrganization, fn () => OrganizationMember::create([
        'organization_id' => $otherOrganization->id,
        'user_id' => $manager->user_id,
        'role' => OrganizationRole::HrManager,
        'status' => 'active',
    ]));
    app(OrganizationContext::class)->runInOrganization($otherOrganization, fn () => OrganizationMember::create([
        'organization_id' => $otherOrganization->id,
        'user_id' => $target->user_id,
        'role' => OrganizationRole::Recruiter,
        'status' => 'active',
    ]));
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.users.suspend', $target->user))
        ->assertRedirect();

    expect($target->user->fresh()->status)->toBe(UserStatus::Suspended);
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $manager->user_id,
        'scope' => 'organization',
        'organization_id' => $organization->id,
        'category' => NotificationCategory::OrganizationModeration->value,
        'subject_type' => 'user',
        'subject_id' => $target->user_id,
    ]);
    $this->assertDatabaseMissing('notifications', [
        'notifiable_id' => $target->user_id,
        'category' => NotificationCategory::OrganizationModeration->value,
        'subject_type' => 'user',
    ]);
    expect(WorkflowNotification::query()->where('notifiable_id', $manager->user_id)
        ->where('category', NotificationCategory::OrganizationModeration->value)
        ->where('subject_type', 'user')->where('subject_id', $target->user_id)
        ->pluck('organization_id')->sort()->values()->all())
        ->toEqualCanonicalizing([$organization->id, $otherOrganization->id]);

    $this->actingAs($manager->user)->get(route('notifications.index'))
        ->assertOk()->assertSee('Organization account update')->assertSee('account was suspended');
});

test('organization members can choose email delivery for moderation notices', function () {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)->patch(route('settings.notifications.update'), [
        'email_preferences' => [NotificationCategory::OrganizationModeration->value => '1'],
        'membership_email_preferences' => [NotificationCategory::OrganizationModeration->value => 'off'],
    ])->assertRedirect(route('settings.edit'));

    $this->assertDatabaseHas('notification_preferences', [
        'user_id' => $owner->id,
        'category' => NotificationCategory::OrganizationModeration->value,
        'email_enabled' => true,
    ]);
    expect(OrganizationMemberPreference::query()->whereHas('membership', fn ($query) => $query
        ->where('organization_id', $organization->id)->where('user_id', $owner->id))
        ->value('organization_moderation_email'))->toBeFalse();
});
