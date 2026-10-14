<?php

use App\Enums\OrganizationRole;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\JobPostMember;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Support\OrganizationContext;
use App\Support\OrganizationPermissions;
use Illuminate\Support\Facades\Gate;

dataset('organization permission matrix', function (): array {
    $rows = [
        'view_jobs' => [true, true, true, true, true],
        'create_jobs' => [true, true, true, false, false],
        'edit_jobs' => [true, true, true, false, false],
        'view_candidates' => [true, true, true, true, true],
        'view_documents' => [true, true, true, true, false],
        'assign_candidates' => [true, true, true, false, false],
        'move_candidates' => [true, true, true, true, false],
        'select_candidates' => [true, true, false, true, false],
        'add_notes' => [true, true, true, true, true],
        'submit_evaluations' => [true, true, true, true, true],
        'view_all_evaluations' => [true, true, true, true, false],
        'create_tasks' => [true, true, true, true, false],
        'own_tasks' => [true, true, true, true, true],
        'manage_team' => [true, true, false, false, false],
        'hiring_settings' => [true, false, false, false, false],
    ];
    $cases = [];

    foreach ($rows as $permission => $expected) {
        foreach (OrganizationRole::cases() as $index => $role) {
            $cases[$role->value.' '.$permission] = [$role, $permission, $expected[$index]];
        }
    }

    return $cases;
});

it('enforces each fixed role permission for an assigned interview candidate', function (OrganizationRole $role, string $permission, bool $expected) {
    app(OrganizationContext::class)->set(null, true);
    $owner = User::factory()->employer()->create();
    $organization = $owner->organizationMemberships()->firstOrFail()->organization;
    $member = User::factory()->create(['role' => 'employer']);
    OrganizationMember::factory()->create(['organization_id' => $organization->id, 'user_id' => $member->id, 'role' => $role]);
    $job = Job::factory()->create(['employer_id' => $owner->id]);
    $application = ApplicationForm::factory()->create(['job_id' => $job->id, 'status' => 'interview']);
    JobPostMember::create(['organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $member->id]);
    app(OrganizationContext::class)->set($organization);

    expect(app(OrganizationPermissions::class)->can($member, $permission, $job, $application))->toBe($expected);

    $applicationAbilities = ['view_candidates' => 'viewForEmployer', 'view_documents' => 'viewDocuments',
        'assign_candidates' => 'assign', 'move_candidates' => 'managePipeline', 'select_candidates' => 'select',
        'add_notes' => 'addNote', 'submit_evaluations' => 'submitEvaluation', 'view_all_evaluations' => 'viewEvaluations', 'create_tasks' => 'createTask'];

    if (isset($applicationAbilities[$permission])) {
        expect(Gate::forUser($member)->allows($applicationAbilities[$permission], $application))->toBe($expected);
    }

    if ($permission === 'create_jobs') {
        expect(Gate::forUser($member)->allows('create', Job::class))->toBe($expected);
    }

    if ($permission === 'edit_jobs') {
        expect(Gate::forUser($member)->allows('update', $job))->toBe($expected);
    }

    if ($permission === 'manage_team') {
        expect(Gate::forUser($member)->allows('manageTeam', $organization))->toBe($expected);
    }

    if ($permission === 'hiring_settings') {
        expect(Gate::forUser($member)->allows('update', $organization))->toBe($expected);
    }
})->with('organization permission matrix');
