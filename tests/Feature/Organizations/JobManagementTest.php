<?php

use App\Enums\JobStatus;
use App\Enums\JobScreeningQuestionType;
use App\Enums\OrganizationRole;
use App\Models\Job;
use App\Models\JobScreeningQuestion;
use App\Models\JobPostMember;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Services\JobCountryService;
use App\Support\OrganizationContext;
use Illuminate\Database\Schema\Blueprint;
use Nnjeim\World\Models\Country;

beforeEach(function () {
    $countryClass = config('world.models.countries', Country::class);
    $model = new $countryClass;
    $schema = $model->getConnection()->getSchemaBuilder();

    if (! $schema->hasTable($model->getTable())) {
        $schema->create($model->getTable(), function (Blueprint $table) {
            $table->id();
            $table->string('iso2', 2)->unique();
            $table->string('name');
        });
    }

    $model->newQuery()->updateOrCreate(['iso2' => 'NG'], ['name' => 'Nigeria']);
    $model->newQuery()->updateOrCreate(['iso2' => 'GB'], ['name' => 'United Kingdom']);
    app()->forgetInstance(JobCountryService::class);
});

function jobPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Backend Engineer',
        'description' => '<p>We need a backend engineer to build and maintain our services.</p>',
        'company' => 'Acme Co',
        'category' => 'Engineering',
        'country_code' => 'NG',
        'employment_type' => 'full_time',
        'workplace_type' => 'remote',
        'start_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'action' => 'publish',
    ], $overrides);
}

function latestJob(): Job
{
    return Job::withoutGlobalScope(OrganizationScope::class)->latest('id')->firstOrFail();
}

function createOrgJob(Organization $organization, User $user, array $overrides = []): Job
{
    return app(OrganizationContext::class)->runInOrganization($organization, fn () => $user->jobs()->create(array_merge([
        'organization_id' => $organization->id,
        'title' => 'Existing Job',
        'description' => '<p>An existing job description.</p>',
        'company' => 'Acme Co',
        'location' => 'Remote',
        'employment_type' => 'full_time',
        'workplace_type' => 'remote',
        'start_date' => now(),
        'due_date' => now()->addDays(30),
        'status' => JobStatus::Open,
    ], $overrides)));
}

function completeJobManagementMemberOnboarding(OrganizationMember $member): void
{
    $member->onboardingProgress()->updateOrCreate([], [
        'current_step' => 'complete',
        'completed_steps' => ['welcome', 'profile', 'team', 'finish'],
        'completed_at' => now(),
    ]);
}

test('an owner can publish a job using only country for its location', function () {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload(['action' => 'publish']))
        ->assertRedirect();

    expect(latestJob()->status)->toBe(JobStatus::Open);
    expect(latestJob()->country_code)->toBe('NG');
    expect(latestJob()->location)->toBeNull();
});

test('submitted legacy location fields are ignored while existing stored locations remain intact', function () {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload([
        'location' => ['unexpected' => 'client location data'],
    ]))->assertSessionHasNoErrors();

    expect(latestJob()->location)->toBeNull();
    expect(latestJob()->country_code)->toBe('NG');

    $legacyJob = createOrgJob($organization, $owner, ['location' => 'Lagos, Nigeria']);
    $this->actingAs($owner)->patch(route('organisations.jobs.update', $legacyJob), jobPayload([
        'country_code' => 'GB',
        'location' => 'An untrusted replacement',
    ]))->assertSessionHasNoErrors();

    expect($legacyJob->fresh()->location)->toBe('Lagos, Nigeria');
    expect($legacyJob->fresh()->country_code)->toBe('GB');
    $this->get(route('organisations.jobs.show', $legacyJob))
        ->assertOk()->assertSee('United Kingdom')->assertDontSee('Lagos, Nigeria');
    $this->get(route('jobs.show', $legacyJob))
        ->assertOk()->assertSee('United Kingdom')->assertDontSee('Lagos, Nigeria');
});

test('a job country must be selected from the installed world countries', function (?string $countryCode) {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload([
        'country_code' => $countryCode,
        'country' => 'Nigeria',
    ]))->assertSessionHasErrors('country_code');

    expect(Job::withoutGlobalScope(OrganizationScope::class)->count())->toBe(0);
})->with([
    'missing selection' => [null],
    'unknown code' => ['ZZ'],
    'name instead of code' => ['Nigeria'],
    'real country absent from installed dataset' => ['US'],
]);

test('workplace type is required and only accepts the supported options', function () {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload(['workplace_type' => null]))
        ->assertSessionHasErrors('workplace_type');
    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload(['workplace_type' => 'flexible']))
        ->assertSessionHasErrors('workplace_type');

    expect(Job::withoutGlobalScope(OrganizationScope::class)->count())->toBe(0);
});

test('job creation uses the registered organization name and persists workplace type and association', function () {
    [$organization, $owner] = organizationWithOwner();
    $organization->update(['name' => 'Registered Company']);

    $this->actingAs($owner)->get(route('organisations.jobs.create'))
        ->assertOk()
        ->assertSee('value="Registered Company"', false)
        ->assertSee('name="company"', false)
        ->assertSee('readonly', false)
        ->assertSee('value="remote"', false)
        ->assertSee('value="hybrid"', false)
        ->assertSee('value="on_site"', false);

    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload([
        'company' => 'Untrusted submitted name',
        'workplace_type' => 'on_site',
    ]))->assertRedirect()->assertSessionHasNoErrors();

    $job = latestJob();
    expect($job->organization_id)->toBe($organization->id)
        ->and($job->company)->toBe('Registered Company')
        ->and($job->workplace_type)->toBe('on_site')
        ->and($job->workplaceTypeLabel())->toBe('On-site');
});

test('workplace type can be changed when editing a job', function () {
    [$organization, $owner] = organizationWithOwner();
    $job = createOrgJob($organization, $owner, ['workplace_type' => 'remote']);

    $this->actingAs($owner)->patch(route('organisations.jobs.update', $job), jobPayload([
        'workplace_type' => 'hybrid',
    ]))->assertRedirect()->assertSessionHasNoErrors();

    expect($job->fresh()->workplace_type)->toBe('hybrid')
        ->and($job->fresh()->workplaceTypeLabel())->toBe('Hybrid');
});

test('organizations can add and edit vacancy-specific screening questions', function () {
    [$organization, $owner] = organizationWithOwner();
    $payload = jobPayload([
        'screening_questions_submitted' => '1',
        'screening_questions' => [[
            'prompt' => 'How many years of relevant experience do you have?',
            'type' => JobScreeningQuestionType::ShortText->value,
            'required' => '1',
        ]],
    ]);

    $this->actingAs($owner)->post(route('organisations.jobs.store'), $payload)
        ->assertRedirect()->assertSessionHasNoErrors();
    $job = latestJob();
    $question = $job->screeningQuestions()->sole();

    expect($question->prompt)->toBe('How many years of relevant experience do you have?')
        ->and($question->type)->toBe(JobScreeningQuestionType::ShortText)
        ->and($question->is_required)->toBeTrue();

    $this->actingAs($owner)->patch(route('organisations.jobs.update', $job), jobPayload([
        'screening_questions_submitted' => '1',
        'screening_questions' => [[
            'id' => $question->id,
            'prompt' => 'Share a relevant accomplishment from your recent work.',
            'type' => JobScreeningQuestionType::LongText->value,
            'required' => '0',
        ]],
    ]))->assertRedirect()->assertSessionHasNoErrors();

    expect($job->screeningQuestions()->sole()->id)->toBe($question->id)
        ->and($job->screeningQuestions()->sole()->prompt)->toBe('Share a relevant accomplishment from your recent work.')
        ->and($job->screeningQuestions()->sole()->type)->toBe(JobScreeningQuestionType::LongText)
        ->and($job->screeningQuestions()->sole()->is_required)->toBeTrue();
});

test('screening question edits reject IDs owned by another vacancy and allow explicit removal', function () {
    [$organization, $owner] = organizationWithOwner();
    $first = createOrgJob($organization, $owner, ['title' => 'First vacancy']);
    $second = createOrgJob($organization, $owner, ['title' => 'Second vacancy']);
    $foreignQuestion = $second->screeningQuestions()->create([
        'prompt' => 'Describe a challenge you solved on the job.',
        'type' => JobScreeningQuestionType::ShortText,
        'is_required' => false,
        'position' => 0,
    ]);

    $this->actingAs($owner)->patch(route('organisations.jobs.update', $first), jobPayload([
        'screening_questions_submitted' => '1',
        'screening_questions' => [[
            'id' => $foreignQuestion->id,
            'prompt' => 'Try to attach another vacancy question here.',
            'type' => JobScreeningQuestionType::ShortText->value,
            'required' => '0',
        ]],
    ]))->assertSessionHasErrors('screening_questions.0.id');

    $this->patch(route('organisations.jobs.update', $second), jobPayload([
        'screening_questions_submitted' => '1',
        'screening_questions' => [],
    ]))->assertRedirect()->assertSessionHasNoErrors();

    expect(JobScreeningQuestion::query()->whereKey($foreignQuestion->id)->exists())->toBeFalse();
});

test('a job stores normalized country codes and ignores submitted country names', function () {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload([
        'country_code' => ' ng ',
        'country' => 'United Kingdom',
    ]))->assertSessionHasNoErrors();

    $job = latestJob();
    expect($job->country_code)->toBe('NG');

    $this->actingAs($owner)->patch(route('organisations.jobs.update', $job), jobPayload([
        'country_code' => 'gb',
    ]))->assertSessionHasNoErrors();

    expect($job->fresh()->country_code)->toBe('GB');
    $this->get(route('organisations.jobs.edit', $job))
        ->assertOk()->assertSee('value="GB" selected', false);
    $this->get(route('organisations.jobs.show', $job))
        ->assertOk()->assertSee('United Kingdom');
});

test('editing a legacy job requires a real country without guessing its location', function () {
    [$organization, $owner] = organizationWithOwner();
    $job = createOrgJob($organization, $owner, ['location' => 'Lagos, Nigeria']);
    expect($job->country_code)->toBeNull();

    foreach ([null, 'ZZ'] as $countryCode) {
        $this->actingAs($owner)->patch(route('organisations.jobs.update', $job), jobPayload([
            'country_code' => $countryCode,
        ]))->assertSessionHasErrors('country_code');
    }

    expect($job->fresh()->country_code)->toBeNull();
});

test('job country validation does not allow fallback codes when world data is unavailable', function () {
    [$organization, $owner] = organizationWithOwner();
    $countryClass = config('world.models.countries', Country::class);
    $model = new $countryClass;
    $model->getConnection()->getSchemaBuilder()->drop($model->getTable());
    app()->forgetInstance(JobCountryService::class);

    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload())
        ->assertSessionHasErrors('country_code');
    expect(Job::withoutGlobalScope(OrganizationScope::class)->count())->toBe(0);
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
    $recruiterMembership = addOrganizationMember($organization, OrganizationRole::Recruiter);
    $interviewerMembership = addOrganizationMember($organization, OrganizationRole::Interviewer);
    completeJobManagementMemberOnboarding($recruiterMembership);
    completeJobManagementMemberOnboarding($interviewerMembership);

    $this->actingAs($recruiterMembership->user)->post(route('organisations.jobs.store'), jobPayload())->assertRedirect();
    $this->actingAs($interviewerMembership->user)->post(route('organisations.jobs.store'), jobPayload())->assertForbidden();
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
        ->assertOk()->assertSee('Save as draft')->assertSee('Publish')
        ->assertSee('name="country_code"', false)->assertDontSee('name="location"', false);
});

test('the job edit form renders with the job values', function () {
    [$organization, $owner] = organizationWithOwner();
    $job = createOrgJob($organization, $owner, ['title' => 'Backend Engineer']);

    $this->actingAs($owner)->get(route('organisations.jobs.edit', $job))
        ->assertOk()->assertSee('Backend Engineer');
});

test('quill alignment/indent/code-block html is stripped to clean markup on save', function () {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload([
        'description' => '<p class="ql-align-center">A</p><div class="ql-code-block-container"><div class="ql-code-block">x</div></div><ul><li>b</li></ul>',
    ]))->assertRedirect();

    expect(latestJob()->description)->not->toContain('ql-align')->not->toContain('<div')->toContain('<ul>');
});

test('the jobs index lists org jobs by role and shows draft badges to the owner', function () {
    [$organization, $owner] = organizationWithOwner();
    createOrgJob($organization, $owner, ['title' => 'Open Role', 'status' => JobStatus::Open]);
    createOrgJob($organization, $owner, ['title' => 'Draft Role', 'status' => JobStatus::Draft]);

    $this->actingAs($owner)->get(route('organisations.jobs.index'))
        ->assertOk()->assertSee('Open Role')->assertSee('Draft Role')->assertSee('Draft');
});

test('delete is hidden from a recruiter but shown to the owner', function () {
    [$organization, $owner] = organizationWithOwner();
    $recruiterMembership = addOrganizationMember($organization, OrganizationRole::Recruiter);
    completeJobManagementMemberOnboarding($recruiterMembership);
    $recruiter = $recruiterMembership->user;
    $job = createOrgJob($organization, $owner, ['title' => 'Shared Role']);
    app(OrganizationContext::class)->runInOrganization($organization, fn () => JobPostMember::create([
        'organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $recruiter->id,
    ]));

    $this->actingAs($owner)->get(route('organisations.jobs.index'))
        ->assertSee('nw-row-actions__item--danger', false)
        ->assertSee('Delete job permanently');
    $this->actingAs($recruiter)->get(route('organisations.jobs.index'))
        ->assertOk()->assertSee('Shared Role')->assertDontSee('nw-row-actions__item--danger', false);
});

test('an owner can close and then delete a job', function () {
    [$organization, $owner] = organizationWithOwner();
    $job = createOrgJob($organization, $owner, []);

    $this->actingAs($owner)->post(route('organisations.jobs.close', $job), ['confirm_closure' => '1'])->assertRedirect();
    expect(Job::withoutGlobalScope(OrganizationScope::class)->find($job->id)->status)->toBe(JobStatus::Closed);

    $this->actingAs($owner)->delete(route('organisations.jobs.destroy', $job))->assertRedirect(route('organisations.jobs.index'));
    expect(Job::withoutGlobalScope(OrganizationScope::class)->whereKey($job->id)->exists())->toBeFalse();
});

test('a suspended job cannot be reopened by publishing an edit', function () {
    [$organization, $owner] = organizationWithOwner();
    $job = createOrgJob($organization, $owner, ['title' => 'Held Role', 'status' => JobStatus::Suspended]);

    $this->actingAs($owner)
        ->from(route('organisations.jobs.edit', $job))
        ->patch(route('organisations.jobs.update', $job), jobPayload(['action' => 'publish']))
        ->assertSessionHasErrors();

    expect(Job::withoutGlobalScope(OrganizationScope::class)->find($job->id)->status)->toBe(JobStatus::Suspended);
});

test('organization users see a Jobs link in navigation', function () {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)->get(route('organisations.dashboard'))
        ->assertOk()->assertSee(route('organisations.jobs.index'), false);
});
