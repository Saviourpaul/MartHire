<?php

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Notification;

test('a recruiter cannot view or manage the hiring team', function () {
    [$organization, $owner] = organizationWithOwner();
    $recruiterMember = addOrganizationMember($organization, OrganizationRole::Recruiter);
    $otherMember = addOrganizationMember($organization, OrganizationRole::HiringManager);

    $this->actingAs($recruiterMember->user)->get(route('organisations.team.index'))->assertForbidden();
    $this->actingAs($recruiterMember->user)->post(route('organisations.team.invitations.store'), ['email' => 'new@example.com', 'role' => 'recruiter'])->assertForbidden();
    $this->actingAs($recruiterMember->user)->patch(route('organisations.team.members.update', $otherMember), ['role' => 'recruiter'])->assertForbidden();
    $this->actingAs($recruiterMember->user)->delete(route('organisations.team.members.destroy', $otherMember))->assertForbidden();
});

test('an owner can invite a new teammate', function () {
    Notification::fake();
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)
        ->post(route('organisations.team.invitations.store'), ['email' => 'teammate@example.com', 'role' => 'recruiter'])
        ->assertRedirect(route('organisations.team.index'))
        ->assertSessionHasNoErrors();

    expect(TeamInvitation::where('organization_id', $organization->id)->where('email', 'teammate@example.com')->exists())->toBeTrue();
});

test('an hr manager cannot invite someone as owner', function () {
    [$organization, $owner] = organizationWithOwner();
    $hr = addOrganizationMember($organization, OrganizationRole::HrManager)->user;

    $this->actingAs($hr)
        ->post(route('organisations.team.invitations.store'), ['email' => 'new@example.com', 'role' => 'owner'])
        ->assertForbidden();
});

test('an owner can change a member role and status', function () {
    [$organization, $owner] = organizationWithOwner();
    $member = addOrganizationMember($organization, OrganizationRole::Recruiter);

    $this->actingAs($owner)
        ->patch(route('organisations.team.members.update', $member), ['role' => 'hiring_manager', 'status' => 'suspended'])
        ->assertRedirect(route('organisations.team.index'));

    expect($member->fresh()->role)->toBe(OrganizationRole::HiringManager);
    expect($member->fresh()->status)->toBe(OrganizationMemberStatus::Suspended);
});

test('an owner can remove a member', function () {
    [$organization, $owner] = organizationWithOwner();
    $member = addOrganizationMember($organization, OrganizationRole::Recruiter);

    $this->actingAs($owner)
        ->delete(route('organisations.team.members.destroy', $member))
        ->assertRedirect(route('organisations.team.index'));

    expect($member->fresh()->trashed())->toBeTrue();
});

test('the sole active owner cannot be demoted or removed', function () {
    [$organization, $owner] = organizationWithOwner();
    $ownerMembership = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
        ->where('organization_id', $organization->id)->where('user_id', $owner->id)->first();

    $this->actingAs($owner)
        ->patch(route('organisations.team.members.update', $ownerMembership), ['role' => 'recruiter', 'status' => 'active'])
        ->assertSessionHasErrors();

    $this->actingAs($owner)
        ->delete(route('organisations.team.members.destroy', $ownerMembership))
        ->assertSessionHasErrors();

    expect($ownerMembership->fresh()->role)->toBe(OrganizationRole::Owner);
});
