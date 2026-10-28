<?php

use App\Enums\JobStatus;
use App\Enums\OrganizationRole;
use App\Models\Job;
use App\Models\Scopes\OrganizationScope;

function jobPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Backend Engineer',
        'description' => '<p>We need a backend engineer to build and maintain our services.</p>',
        'company' => 'Acme Co',
        'category' => 'Engineering',
        'location' => 'Remote',
        'employment_type' => 'full_time',
        'start_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'action' => 'publish',
    ], $overrides);
}

function latestJob(): Job
{
    return Job::withoutGlobalScope(OrganizationScope::class)->latest('id')->firstOrFail();
}

function createOrgJob(\App\Models\Organization $organization, \App\Models\User $user, array $overrides = []): Job
{
    return app(\App\Support\OrganizationContext::class)->runInOrganization($organization, fn () => $user->jobs()->create(array_merge([
        'organization_id' => $organization->id,
        'title' => 'Existing Job',
        'description' => '<p>An existing job description.</p>',
        'company' => 'Acme Co',
        'location' => 'Remote',
        'employment_type' => 'full_time',
        'start_date' => now(),
        'due_date' => now()->addDays(30),
        'status' => JobStatus::Open,
    ], $overrides)));
}

test('an owner can publish a job', function () {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload(['action' => 'publish']))
        ->assertRedirect();

    expect(latestJob()->status)->toBe(JobStatus::Open);
});

test('a job can be saved as a draft and is excluded from the public board and applications', function () {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload(['action' => 'save_draft']))
        ->assertRedirect();

    $job = latestJob();
    expect($job->status)->toBe(JobStatus::Draft);
    expect($job->isAcceptingApplications())->toBeFalse();
    expect(Job::withoutGlobalScope(OrganizationScope::class)->open()->whereKey($job->id)->exists())->toBeFalse();
});

test('a recruiter can create but a user without create_jobs cannot', function () {
    [$organization, $owner] = organizationWithOwner();
    $recruiter = addOrganizationMember($organization, OrganizationRole::Recruiter)->user;
    $interviewer = addOrganizationMember($organization, OrganizationRole::Interviewer)->user;

    $this->actingAs($recruiter)->post(route('organisations.jobs.store'), jobPayload())->assertRedirect();
    $this->actingAs($interviewer)->post(route('organisations.jobs.store'), jobPayload())->assertForbidden();
});

test('job creation validates required fields and description text', function () {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload(['title' => '']))->assertSessionHasErrors('title');
    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload(['description' => '<p>   </p>']))->assertSessionHasErrors('description');
    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload(['employment_type' => 'bogus']))->assertSessionHasErrors('employment_type');
});

test('a submitted description is sanitized server-side', function () {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload([
        'description' => '<p>Hi</p><script>alert(1)</script><div class="x" onclick="e()">z</div>',
    ]))->assertRedirect();

    expect(latestJob()->description)->not->toContain('<script>')->not->toContain('onclick');
});

test('the job create form renders for an authorized user', function () {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)->get(route('organisations.jobs.create'))
        ->assertOk()->assertSee('Save as draft')->assertSee('Publish');
});

test('the job edit form renders with the job values', function () {
    [$organization, $owner] = organizationWithOwner();
    $job = createOrgJob($organization, $owner, ['title' => 'Backend Engineer']);

    $this->actingAs($owner)->get(route('organisations.jobs.edit', $job))
        ->assertOk()->assertSee('Backend Engineer');
});
