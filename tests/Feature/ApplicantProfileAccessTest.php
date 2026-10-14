<?php

use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use App\Support\OrganizationContext;

test('admins view read only profiles and employers use accessible candidate applications', function () {
    app(OrganizationContext::class)->set(null, true);
    $admin = User::factory()->admin()->create();
    $employer = User::factory()->employer()->create();
    $applicant = User::factory()->completeApplicantProfile()->create();
    $job = Job::factory()->for($employer, 'employer')->create();
    $application = ApplicationForm::factory()->for($job, 'job')->for($applicant, 'applicant')->create();

    $this->actingAs($admin)
        ->get(route('applicants.profile.show', $applicant))
        ->assertOk()
        ->assertSee('Applicant Profile')
        ->assertSee('This profile is read-only.');

    $this->actingAs($employer)
        ->get(route('applicants.profile.show', $applicant))
        ->assertRedirect(route('employer.applications.show', $application));
});

test('employers cannot browse profiles without an accessible application', function () {
    $employer = User::factory()->employer()->create();
    $applicant = User::factory()->completeApplicantProfile()->create();

    $this->actingAs($employer)->get(route('applicants.profile.show', $applicant))->assertForbidden();
});

test('applicants can not view other applicant profiles through the read only route', function () {
    $viewer = User::factory()->create();
    $applicant = User::factory()->completeApplicantProfile()->create();

    $this->actingAs($viewer)
        ->get(route('applicants.profile.show', $applicant))
        ->assertForbidden();
});

test('non applicant profile ids are not shown through applicant profile route', function () {
    $admin = User::factory()->admin()->create();
    $employer = User::factory()->employer()->create();

    $this->actingAs($admin)
        ->get(route('applicants.profile.show', $employer))
        ->assertNotFound();
});

test('employers can not view suspended applicant profiles', function () {
    $employer = User::factory()->employer()->create();
    $applicant = User::factory()->completeApplicantProfile()->suspended()->create();

    $this->actingAs($employer)
        ->get(route('applicants.profile.show', $applicant))
        ->assertForbidden();
});
