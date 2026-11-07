<?php

use App\Enums\OrganizationRole;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use Illuminate\Support\Facades\Broadcast;

test('admin workspace channel is limited to active verified administrators', function () {
    $authorize = Broadcast::getChannels()->get('admins');
    $admin = User::factory()->admin()->create();
    $organization = User::factory()->organization()->create();
    $suspendedAdmin = User::factory()->admin()->create(['status' => 'suspended']);
    $unverifiedAdmin = User::factory()->admin()->create(['email_verified_at' => null]);

    expect($authorize($admin))->toBeTrue()
        ->and($authorize($organization))->toBeFalse()
        ->and($authorize($suspendedAdmin))->toBeFalse()
        ->and($authorize($unverifiedAdmin))->toBeFalse();
});

test('organization channel only admits an active verified member of that organization', function () {
    [$organization, $owner] = organizationWithOwner();
    $otherOrganization = User::factory()->organization()->create();
    $membership = app(OrganizationProvisioner::class)->ensureForEmployer($otherOrganization);
    $inactive = addOrganizationMember($organization, OrganizationRole::Recruiter);
    $inactive->update(['status' => 'suspended']);
    $unverified = User::factory()->organization()->create(['email_verified_at' => null]);

    $authorize = Broadcast::getChannels()->get('organizations.{organization}');

    expect($authorize($owner, $organization->id))->toBeTrue()
        ->and($authorize($owner, $membership->id))->toBeFalse()
        ->and($authorize($inactive->user, $organization->id))->toBeFalse()
        ->and($authorize($unverified, $organization->id))->toBeFalse();
});

test('applicant workspace channel only admits the matching active verified account', function () {
    $applicant = User::factory()->create();
    $otherApplicant = User::factory()->create();
    $suspendedApplicant = User::factory()->create(['status' => 'suspended']);
    $authorize = Broadcast::getChannels()->get('App.Models.User.{id}');

    expect($authorize($applicant, $applicant->id))->toBeTrue()
        ->and($authorize($applicant, $otherApplicant->id))->toBeFalse()
        ->and($authorize($suspendedApplicant, $suspendedApplicant->id))->toBeFalse();
});
