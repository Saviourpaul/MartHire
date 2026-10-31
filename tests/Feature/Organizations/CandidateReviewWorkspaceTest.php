<?php

use App\Enums\CandidatePipelineStage;
use App\Enums\OrganizationRole;
use App\Models\ApplicationForm;
use App\Models\JobPostMember;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CandidateReviewService;
use App\Services\OrganizationProvisioner;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Storage;

function reviewWorkspaceApplication(User $owner, array $attributes = []): ApplicationForm
{
    $organization = app(OrganizationProvisioner::class)->ensureForEmployer($owner);

    return app(OrganizationContext::class)->runInOrganization($organization, function () use ($owner, $organization, $attributes): ApplicationForm {
        $applicant = User::factory()->create([
            'first_name' => 'Current', 'last_name' => 'Profile', 'email' => 'current-profile@example.test',
            'phone' => '+14165550123', 'profile_image_path' => 'profile-images/current.jpg',
        ]);
        $job = $owner->jobs()->create([
            'organization_id' => $organization->id, 'title' => 'Review Workspace Engineer',
            'description' => 'A full description of an engineering role for the review workspace.',
            'company' => 'Review Co', 'location' => 'Nigeria', 'employment_type' => 'full_time',
            'start_date' => now(), 'due_date' => now()->addMonth(),
        ]);

        return ApplicationForm::create([
            'organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $applicant->id,
            'reference' => 'APP-REVIEW-'.$applicant->id, 'status' => CandidatePipelineStage::Submitted,
            'submitted_at' => now(), 'first_name' => 'Submitted', 'last_name' => 'Candidate',
            'email' => 'submitted-candidate@example.test', 'phone' => '+442079460018',
            'country_code' => 'NG', 'country' => 'Nigeria', 'state' => 'Lagos', 'city' => 'Ikeja',
            'profile_image_path' => 'profile-images/submitted.jpg', ...$attributes,
        ]);
    });
}

test('candidate workspace reviews the submission snapshot instead of a later profile edit', function () {
    Storage::fake('public');
    [$organization, $owner] = organizationWithOwner();
    $application = reviewWorkspaceApplication($owner, ['date_of_birth' => '1995-02-03', 'gender' => 'female']);

    $this->actingAs($owner)->get(route('organisations.candidates.show', $application))->assertOk()
        ->assertViewHas('review', function (array $review): bool {
            expect($review['profile']['name'])->toBe('Submitted Candidate')
                ->and($review['profile']['email'])->toBe('submitted-candidate@example.test')
                ->and($review['profile']['phone'])->toBe('+442079460018')
                ->and($review['profile']['date_of_birth'])->toBe('3 Feb 1995')
                ->and($review['profile']['avatar_url'])->toBe(Storage::disk('public')->url('profile-images/submitted.jpg'))
                ->and($review['profile'])->not->toHaveKey('profile_image_path');

            return true;
        })->assertSee('Submitted Candidate')->assertSee('submitted-candidate@example.test')
        ->assertDontSee('current-profile@example.test');
});

test('legacy empty snapshot fields fall back to the profile while unsafe avatar keys are rejected', function () {
    [$organization, $owner] = organizationWithOwner();
    $application = reviewWorkspaceApplication($owner, [
        'first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '',
        'profile_image_path' => 'https://untrusted.example/avatar.svg',
    ]);
    $review = app(OrganizationContext::class)->runInOrganization($organization,
        fn () => app(CandidateReviewService::class)->build($application, $owner));

    expect($review['profile']['name'])->toBe('Current Profile')
        ->and($review['profile']['email'])->toBe('current-profile@example.test')
        ->and($review['profile']['avatar_url'])->toBeNull();

    foreach (['../private.txt', '%2e%2e/avatar.svg', '\\untrusted\\share', '/absolute/avatar.jpg'] as $path) {
        $application->profile_image_path = $path;
        $review = app(OrganizationContext::class)->runInOrganization($organization,
            fn () => app(CandidateReviewService::class)->build($application, $owner));
        expect($review['profile']['avatar_url'])->toBeNull();
    }
});

test('transition remarks and readable actions are presented without leaking raw audit payloads', function () {
    [$organization, $owner] = organizationWithOwner();
    $application = reviewWorkspaceApplication($owner);
    app(OrganizationContext::class)->runInOrganization($organization, function () use ($application, $owner): void {
        $application->statusHistories()->create([
            'from_status' => CandidatePipelineStage::Submitted, 'to_status' => CandidatePipelineStage::Shortlisted,
            'changed_by' => $owner->id, 'remarks' => 'Strong experience with Laravel.', 'created_at' => now()->subMinute(),
        ]);
        $application->update(['status' => CandidatePipelineStage::Shortlisted]);
        app(ActivityLogger::class)->record($application->organization_id, $application, $owner, 'pipeline', [
            'from_stage' => 'submitted', 'to_stage' => 'shortlisted',
        ]);
        app(ActivityLogger::class)->record($application->organization_id, $application, $owner, 'interview', [
            'action' => 'interview_scheduled', 'interview' => ['mode' => 'online', 'start_at' => '2027-02-01T10:00:00Z', 'timezone' => 'Africa/Lagos'],
            'raw_private_token' => 'secret-raw-audit-token',
        ]);
        app(ActivityLogger::class)->record($application->organization_id, $application, $owner, 'email', [
            'action' => 'candidate_email_sent', 'template_type' => 'interview_online', 'rendered_body' => 'hidden-email-body',
        ]);
    });

    $this->actingAs($owner)->get(route('organisations.candidates.show', $application))->assertOk()
        ->assertViewHas('review', function (array $review): bool {
            $entries = collect($review['activity']['entries']);
            expect($entries->where('type', 'pipeline'))->toHaveCount(1)
                ->and($entries->firstWhere('type', 'pipeline')['remarks'])->toBe('Strong experience with Laravel.')
                ->and($entries->firstWhere('type', 'interview')['detail'])->toContain('Africa/Lagos')
                ->and($entries->firstWhere('type', 'email')['title'])->toBe('Candidate email sent');

            return true;
        })->assertSee('Strong experience with Laravel.')->assertSee('Interview scheduled')
        ->assertDontSee('secret-raw-audit-token')->assertDontSee('hidden-email-body');
});

test('interviewer workspace excludes document and email activity and keeps the valid pipeline', function () {
    [$organization, $owner] = organizationWithOwner();
    $interviewer = addOrganizationMember($organization, OrganizationRole::Interviewer)->user;
    $application = reviewWorkspaceApplication($owner, ['status' => CandidatePipelineStage::Interview]);
    app(OrganizationContext::class)->runInOrganization($organization, function () use ($application, $interviewer, $owner): void {
        JobPostMember::create(['organization_id' => $application->organization_id, 'job_id' => $application->job_id, 'user_id' => $interviewer->id]);
        foreach (['document_view', 'email', 'interview'] as $type) {
            app(ActivityLogger::class)->record($application->organization_id, $application, $owner, $type, [
                'action' => match ($type) {
                    'document_view' => 'preview', 'email' => 'candidate_email_sent', default => 'interview_scheduled'
                },
            ]);
        }
    });

    $this->actingAs($interviewer)->get(route('organisations.candidates.show', $application))->assertOk()
        ->assertViewHas('review', function (array $review): bool {
            expect(array_column($review['activity']['entries'], 'type'))->toBe(['interview'])
                ->and($review['pipeline']['stage'])->toBe('interview')
                ->and($review['pipeline']['steps'][2]['state'])->toBe('current');

            return true;
        })->assertDontSee('Document previewed')->assertDontSee('Candidate email sent')
        ->assertDontSee('Move to Selected');
});

test('stage review remarks survive a real move and rejection does not mark unreached stages complete', function () {
    [$organization, $owner] = organizationWithOwner();
    $application = reviewWorkspaceApplication($owner);

    $this->actingAs($owner)->patch(route('organisations.candidates.move', $application), [
        'stage' => 'rejected', 'remarks' => 'The role requires additional database experience.',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $this->get(route('organisations.candidates.show', $application))->assertOk()
        ->assertViewHas('review', function (array $review): bool {
            expect($review['pipeline']['stage'])->toBe('rejected')
                ->and($review['pipeline']['is_terminal'])->toBeTrue()
                ->and($review['pipeline']['steps'][0]['complete'])->toBeTrue()
                ->and($review['pipeline']['steps'][1]['state'])->toBe('upcoming')
                ->and($review['pipeline']['steps'][2]['state'])->toBe('upcoming')
                ->and($review['pipeline']['steps'][3]['state'])->toBe('rejected')
                ->and($review['activity']['entries'][0]['remarks'])->toBe('The role requires additional database experience.');

            return true;
        })->assertSee('The role requires additional database experience.');

    expect($application->fresh()->employer_remarks)->toBe('The role requires additional database experience.');
});

test('candidate activity is bounded and stable when timestamps match', function () {
    [$organization, $owner] = organizationWithOwner();
    $application = reviewWorkspaceApplication($owner);
    $review = app(OrganizationContext::class)->runInOrganization($organization, function () use ($application, $owner): array {
        $this->freezeTime();
        for ($index = 0; $index < 55; $index++) {
            app(ActivityLogger::class)->record($application->organization_id, $application, $owner, 'interview', ['action' => 'interview_scheduled']);
        }
        $service = app(CandidateReviewService::class);
        $first = $service->build($application, $owner);
        $second = $service->build($application, $owner);
        expect(array_column($first['activity']['entries'], 'id'))->toBe(array_column($second['activity']['entries'], 'id'));

        return $first;
    });

    expect($review['activity']['entries'])->toHaveCount(50)->and($review['activity']['has_more'])->toBeTrue();
});
