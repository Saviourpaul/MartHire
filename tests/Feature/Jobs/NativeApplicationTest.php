<?php

use App\Enums\ApplicationDocumentType;
use App\Enums\JobStatus;
use App\Enums\JobScreeningQuestionType;
use App\Enums\NotificationCategory;
use App\Enums\OrganizationRole;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\JobScreeningQuestion;
use App\Models\CandidateJobPreference;
use App\Models\Job;
use App\Models\JobPostMember;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserIdentificationDocument;
use App\Services\ApplicantProfileChecklistService;
use App\Support\ApplicationUploadLimits;
use App\Support\OrganizationContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

function nativeApplicationJob(array $overrides = []): array
{
    [$organization, $owner] = organizationWithOwner();
    $job = app(OrganizationContext::class)->runInOrganization($organization, fn () => $owner->jobs()->create(array_merge([
        'organization_id' => $organization->id,
        'title' => 'Native Application Engineer',
        'description' => '<p>Build reliable services for our customers.</p>',
        'company' => 'Native Hiring Co',
        'location' => 'Lagos',
        'country_code' => 'NG',
        'employment_type' => 'full_time',
        'start_date' => today(),
        'due_date' => today()->addDays(14),
        'status' => JobStatus::Open,
    ], $overrides)));

    return [$job, $owner];
}

function nativeScreeningQuestion(Job $job, array $attributes): JobScreeningQuestion
{
    return $job->screeningQuestions()->create($attributes + [
        'type' => JobScreeningQuestionType::ShortText,
        'prompt' => 'Describe relevant experience for this role.',
        'is_required' => false,
        'position' => 0,
    ]);
}

function nativeApplicationPayload(): array
{
    return [
        'profile_image' => UploadedFile::fake()->createWithContent(
            'profile-photo.png',
            file_get_contents(base_path('tests/Fixtures/applications/profile.png')),
        ),
        'first_name' => 'Alice',
        'last_name' => 'Applicant',
        'email' => 'alice@example.test',
        'phone' => '+2348012345678',
        'country_code' => 'NG',
        'country' => 'Nigeria',
        'state' => 'Lagos',
        'local_government_area' => 'Ikeja',
        'identification_type' => ApplicationDocumentType::NationalIdentityCard->value,
        'identification_document' => UploadedFile::fake()->create('identity.pdf', 10, 'application/pdf'),
        'identity_issuing_country_code' => 'NG',
        'resume' => UploadedFile::fake()->create('resume.pdf', 10, 'application/pdf'),
    ];
}

function completeNativeApplicant(): User
{
    $applicant = User::factory()->create([
        'profile_image_path' => 'profile-images/saved-applicant.png',
        'date_of_birth' => '1995-05-10',
        'phone' => '+2348012345678',
        'address' => '10 Example Avenue',
        'country_code' => 'NG',
        'country' => 'Nigeria',
        'state' => 'Lagos',
        'local_government_area' => 'Ikeja',
        'location_confirmed_at' => now(),
    ]);
    Storage::disk('public')->put('profile-images/saved-applicant.png', 'stored profile image');
    $resumePath = 'candidate-resumes/saved-resume.pdf';
    Storage::disk('local')->put($resumePath, '%PDF-1.4 saved resume');
    $profile = $applicant->applicantProfile()->create([
        'professional_headline' => 'Experienced application engineer',
        'about' => 'I build reliable web applications and enjoy solving complex problems.',
        'resume_path' => $resumePath,
        'resume_original_name' => 'saved-resume.pdf',
        'resume_mime_type' => 'application/pdf',
        'resume_size' => 21,
    ]);
    $profile->selfIdentification()->create([
        'gender_identity' => 'female',
        'disability_status' => 'no',
    ]);
    $profile->employments()->create([
        'company' => 'Example Company', 'title' => 'Application Engineer', 'start_date' => '2022-01-01',
    ]);
    $profile->skills()->create(['name' => 'Laravel']);
    $profile->educations()->create([
        'institution' => 'Example University', 'qualification' => 'BSc Computer Science',
    ]);
    $profile->links()->create(['platform' => 'linkedin', 'url' => 'https://www.linkedin.com/in/alice-example']);
    CandidateJobPreference::create(['user_id' => $applicant->id, 'preferred_roles' => ['Software Engineer']]);

    $identityPath = 'user-identification-documents/'.$applicant->id.'/identity.pdf';
    Storage::disk('local')->put($identityPath, '%PDF-1.4 saved identity');
    UserIdentificationDocument::create([
        'user_id' => $applicant->id,
        'document_type' => ApplicationDocumentType::NationalIdentityCard,
        'file_path' => $identityPath,
        'original_name' => 'saved-identity.pdf',
        'mime_type' => 'application/pdf',
        'size' => 23,
    ]);

    return $applicant;
}

beforeEach(function () {
    config(['rate_limiting.store' => 'array']);
    Storage::fake('local');
    Storage::fake('public');
    Queue::fake();
});

test('an applicant can open a real native job application form', function () {
    [$job] = nativeApplicationJob();

    $this->actingAs(User::factory()->create())
        ->get(route('applications.create', $job))
        ->assertOk()
        ->assertViewIs('client.job-application')
        ->assertSee($job->title)
        ->assertSee(route('applications.store', $job), false)
        ->assertSee('data-max-total-upload-bytes="'.ApplicationUploadLimits::maxTotalFileBytes().'"', false)
        ->assertSee('data-max-single-upload-bytes="'.ApplicationUploadLimits::maxSingleFileBytes().'"', false)
        ->assertSee('data-max-upload-files="'.ApplicationUploadLimits::maxFileCount().'"', false)
        ->assertSee('identification_document', false)
        ->assertDontSee('education_documents[', false)
        ->assertDontSee('name="date_of_birth"', false)
        ->assertDontSee('name="marital_status"', false)
        ->assertSee('Submit application');
});

test('an oversized PHP request is rejected with a clear upload error before form validation', function () {
    [$job] = nativeApplicationJob();
    $requestLimit = ApplicationUploadLimits::requestBodyLimitBytes();
    expect($requestLimit)->not->toBeNull();

    $this->actingAs(User::factory()->create())
        ->withServerVariables(['CONTENT_LENGTH' => (string) ($requestLimit + 1)])
        ->postJson(route('applications.store', $job), [])
        ->assertStatus(413)
        ->assertJsonPath('message', 'The application upload is larger than the server allows. Remove a file or choose smaller files, then try again.');

    expect(ApplicationForm::count())->toBe(0);
});

test('an incomplete profile does not add required profile fields to the application wizard', function () {
    [$job] = nativeApplicationJob();
    $response = $this->actingAs(User::factory()->create())->get(route('applications.create', $job))->assertOk()
        ->assertSee('nativeApplicationForm', false)
        ->assertSee('data-step-title="Your location"', false)
        ->assertDontSee('profile_employment[is_current]', false)
        ->assertDontSee('profile_contact[gender_identity]', false)
        ->assertSee('Review and submit')
        ->assertDontSee('async loadStates', false);

    expect(substr_count($response->getContent(), 'data-wizard-step'))->toBe(4)
        ->and(substr_count($response->getContent(), 'name="gender"'))->toBe(0);
    expect(route('locations.states', absolute: false))->toBe('/lookups/locations/states');
    expect(route('locations.cities', absolute: false))->toBe('/lookups/locations/cities');
});

test('saved profile photo résumé and identification satisfy their required application fields', function () {
    [$job] = nativeApplicationJob();
    $applicant = completeNativeApplicant();

    $response = $this->actingAs($applicant)->get(route('applications.create', $job))->assertOk();

    expect(substr_count($response->getContent(), 'data-wizard-step'))->toBe(4)
        ->and($response->viewData('applicantProfile')->professional_headline)->toBe('Experienced application engineer')
        ->and($response->viewData('profileRequirements')['has_additional_fields'])->toBeFalse()
        ->and($response->viewData('profileRequirements')['profile_image'])->toBeFalse()
        ->and($response->viewData('profileRequirements')['identification'])->toBeFalse();
    $response->assertSee('saved-resume.pdf')->assertSee('saved-identity.pdf');
});

test('saved résumé and identification are reused for every application even when the role has no extra identity rule', function () {
    [$job] = nativeApplicationJob();
    $applicant = completeNativeApplicant();
    $payload = nativeApplicationPayload();
    unset(
        $payload['profile_image'],
        $payload['identification_document'],
        $payload['resume'],
    );
    $payload['identity_issuing_country_code'] = 'NG';

    $this->actingAs($applicant)->withHeaders(['Accept' => 'application/json'])
        ->post(route('applications.store', $job), $payload)
        ->assertCreated();

    $application = ApplicationForm::withoutGlobalScopes()->where('job_id', $job->id)->firstOrFail();
    $documents = $application->documents()->get();
    $resume = $documents->firstWhere('document_type', ApplicationDocumentType::Resume);
    $identity = $documents->firstWhere('document_type', ApplicationDocumentType::NationalIdentityCard);

    expect($documents)->toHaveCount(2)
        ->and($resume)->not->toBeNull()
        ->and($resume->file_path)->not->toBe('candidate-resumes/saved-resume.pdf')
        ->and($identity)->not->toBeNull()
        ->and($identity->file_path)->toStartWith('application-documents/'.$application->id.'/identity-')
        ->and($identity->issuing_country_code)->toBe('NG')
        ->and($application->profile_image_path)->not->toBe($applicant->fresh()->profile_image_path)
        ->and($applicant->applicantProfile()->first()->employments()->count())->toBe(1);
    Storage::disk('local')->assertExists($resume->file_path);
    Storage::disk('local')->assertExists($identity->file_path);
    Storage::disk('public')->assertExists($application->profile_image_path);
});

test('an applicant can submit required application details without completing the general profile', function () {
    [$job] = nativeApplicationJob();
    $applicant = User::factory()->create();
    $this->actingAs($applicant)->withHeaders(['Accept' => 'application/json'])
        ->post(route('applications.store', $job), nativeApplicationPayload())
        ->assertCreated();

    $checklist = app(ApplicantProfileChecklistService::class)->forUser($applicant->fresh());
    expect(ApplicationForm::withoutGlobalScopes()->count())->toBe(1)
        ->and($checklist['completed'])->toBeLessThan($checklist['total'])
        ->and($applicant->applicantProfile()->firstOrFail()->selfIdentification)->toBeNull();
});

test('the native wizard receives a JSON receipt destination without changing private storage', function () {
    [$job] = nativeApplicationJob();
    $applicant = User::factory()->create();
    $response = $this->actingAs($applicant)->withHeaders(['Accept' => 'application/json'])
        ->post(route('applications.store', $job), nativeApplicationPayload());
    $application = ApplicationForm::withoutGlobalScopes()->where('job_id', $job->id)->firstOrFail();

    $response->assertCreated()->assertJsonPath('submitted', true)
        ->assertJsonPath('redirect', route('client.applications.show', $application));
    expect($application->documents)->toHaveCount(2);
    foreach ($application->documents as $document) {
        Storage::disk('local')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($document->file_path);
    }
});

test('the native wizard receives field errors as JSON without creating an application', function () {
    [$job] = nativeApplicationJob();
    $this->actingAs(User::factory()->create())->withHeaders(['Accept' => 'application/json'])
        ->post(route('applications.store', $job), ['first_name' => 'Alice'])
        ->assertUnprocessable()->assertJsonValidationErrors([
            'profile_image', 'last_name', 'email', 'phone', 'country_code', 'country', 'state', 'city',
            'identification_type', 'identification_document', 'identity_issuing_country_code', 'resume',
        ])->assertJsonMissingValidationErrors(['middle_name']);

    expect(ApplicationForm::withoutGlobalScopes()->count())->toBe(0);
});

test('a native application snapshots international phone parts independently of its address', function () {
    [$job] = nativeApplicationJob();
    $applicant = User::factory()->create();
    $this->actingAs($applicant)->withHeaders(['Accept' => 'application/json'])
        ->post(route('applications.store', $job), [...nativeApplicationPayload(), 'phone' => '(202) 555-0123', 'phone_country' => 'US'])
        ->assertCreated();

    $application = ApplicationForm::withoutGlobalScopes()->where('job_id', $job->id)->firstOrFail();
    expect($application->phone)->toBe('+12025550123')->and($application->phone_country)->toBe('US')
        ->and($application->phone_dial_code)->toBe('+1')->and($application->phone_national_number)->toBe('2025550123')
        ->and($application->country_code)->toBe('NG')
        ->and($applicant->fresh()->phone_country)->toBe('US');
});

test('a native application reaches the organization pipeline with private documents and an applicant receipt', function () {
    [$job, $owner] = nativeApplicationJob();
    $applicant = User::factory()->create();
    $organization = Organization::query()->findOrFail($job->organization_id);
    $assignedRecruiter = addOrganizationMember($organization, OrganizationRole::Recruiter);
    $unassignedRecruiter = addOrganizationMember($organization, OrganizationRole::Recruiter);
    foreach ([$assignedRecruiter, $unassignedRecruiter] as $member) {
        app(OrganizationContext::class)->runInOrganization($organization, fn () => $member->onboardingProgress()->updateOrCreate([], [
            'current_step' => 'complete',
            'completed_steps' => ['welcome', 'profile', 'team', 'finish'],
            'completed_at' => now(),
        ]));
    }
    app(OrganizationContext::class)->runInOrganization($organization, fn () => JobPostMember::create([
        'organization_id' => $organization->id,
        'job_id' => $job->id,
        'user_id' => $assignedRecruiter->user_id,
    ]));

    $response = $this->actingAs($applicant)->post(route('applications.store', $job), nativeApplicationPayload());
    $response->assertSessionHasNoErrors();

    $application = ApplicationForm::withoutGlobalScopes()->where('job_id', $job->id)->firstOrFail();
    $response->assertRedirect(route('client.applications.show', $application));
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $owner->id,
        'category' => NotificationCategory::NewApplication->value,
        'subject_type' => 'application',
        'subject_id' => $application->id,
    ]);
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $assignedRecruiter->user_id,
        'category' => NotificationCategory::NewApplication->value,
        'subject_type' => 'application',
        'subject_id' => $application->id,
    ]);
    $this->assertDatabaseMissing('notifications', [
        'notifiable_id' => $unassignedRecruiter->user_id,
        'category' => NotificationCategory::NewApplication->value,
        'subject_type' => 'application',
        'subject_id' => $application->id,
    ]);
    expect($application->user_id)->toBe($applicant->id)
        ->and($application->organization_id)->toBe($job->organization_id)
        ->and($application->email)->toBe('alice@example.test')
        ->and($application->status->value)->toBe('submitted')
        ->and($application->statusHistories()->count())->toBe(1);

    $documents = ApplicationDocument::withoutGlobalScopes()->where('application_form_id', $application->id)->get();
    expect($documents)->toHaveCount(2);
    foreach ($documents as $document) {
        expect($document->organization_id)->toBe($job->organization_id);
        Storage::disk('local')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($document->file_path);
    }
    $profile = $applicant->applicantProfile()->with(['employments', 'educations', 'skills', 'links', 'selfIdentification'])->firstOrFail();
    $checklist = app(ApplicantProfileChecklistService::class)->forUser($applicant->fresh());
    expect($profile->professional_headline)->toBeNull()
        ->and($profile->selfIdentification)->toBeNull()
        ->and($profile->employments)->toBeEmpty()
        ->and($profile->educations)->toBeEmpty()
        ->and($profile->skills)->toBeEmpty()
        ->and($profile->links)->toBeEmpty()
        ->and($checklist['completed'])->toBeLessThan($checklist['total']);
    expect($applicant->fresh()->profile_image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($applicant->fresh()->profile_image_path);

    $this->get(route('client.applications.show', $application))
        ->assertOk()->assertSee($application->reference)->assertSee('Documents received')
        ->assertSee('Résumé / CV')->assertDontSee('resume.pdf');
    $this->actingAs($owner)->get(route('organisations.candidates.index'))
        ->assertOk()->assertSee('Alice Applicant')
        ->assertSee(route('organisations.candidates.show', $application), false);
    $this->actingAs($owner)->get(route('notifications.index'))
        ->assertOk()->assertSee('New application')->assertSee($application->reference);
    $this->actingAs($assignedRecruiter->user)->get(route('notifications.index'))
        ->assertOk()->assertSee('New application')->assertSee($application->reference);
    $this->actingAs($unassignedRecruiter->user)->get(route('notifications.index'))
        ->assertOk()->assertDontSee($application->reference);
    $this->actingAs($owner)->get(route('organisations.candidates.show', $application))
        ->assertOk()->assertSee($application->reference)->assertSee('Alice Applicant');
    $this->actingAs($applicant)->withHeader('X-Live-Tables', 'client-application')
        ->getJson(route('client.applications.show', $application))
        ->assertOk()->assertJsonStructure(['regions' => ['client-application']]);
});

test('repeat native applications redirect to their receipt and cannot create duplicate records', function () {
    [$job] = nativeApplicationJob();
    $applicant = User::factory()->create();
    $this->actingAs($applicant)->post(route('applications.store', $job), nativeApplicationPayload())->assertSessionHasNoErrors();
    $application = ApplicationForm::withoutGlobalScopes()->where('job_id', $job->id)->firstOrFail();

    $this->get(route('applications.create', $job))->assertRedirect(route('client.applications.show', $application));
    $this->post(route('applications.store', $job), nativeApplicationPayload())->assertSessionHasErrors('job');
    expect(ApplicationForm::withoutGlobalScopes()->where('job_id', $job->id)->count())->toBe(1);
});

test('native submission performs one duplicate lookup on the valid submit path', function () {
    [$job] = nativeApplicationJob();
    $applicant = User::factory()->create();
    $duplicateLookups = [];

    DB::listen(function (QueryExecuted $query) use (&$duplicateLookups): void {
        $sql = strtolower($query->sql);
        if (str_contains($sql, 'application_forms') && str_contains($sql, 'user_id') && str_contains($sql, 'exists')) {
            $duplicateLookups[] = $query->sql;
        }
    });

    $this->actingAs($applicant)
        ->post(route('applications.store', $job), nativeApplicationPayload())
        ->assertSessionHasNoErrors();

    expect($duplicateLookups)->toHaveCount(1);
});

test('unavailable native jobs reject application views and submissions', function (JobStatus $status, bool $expired) {
    [$job] = nativeApplicationJob(['status' => $status, 'due_date' => $expired ? today()->subDay() : today()->addDays(14)]);
    $this->actingAs(User::factory()->create());

    $this->get(route('applications.create', $job))->assertNotFound();
    $this->post(route('applications.store', $job), [])->assertNotFound();
    expect(ApplicationForm::withoutGlobalScopes()->count())->toBe(0);
})->with([
    'draft' => [JobStatus::Draft, false],
    'suspended' => [JobStatus::Suspended, false],
    'closed' => [JobStatus::Closed, false],
    'expired' => [JobStatus::Open, true],
]);

test('native applications require a verified applicant and protect receipts from other applicants', function () {
    [$job, $owner] = nativeApplicationJob();
    $this->get(route('applications.create', $job))->assertRedirect(route('login'));
    $this->post(route('applications.store', $job), [])->assertRedirect(route('login'));
    $this->actingAs($owner)->get(route('applications.create', $job))->assertForbidden();
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('applications.create', $job))->assertRedirect(route('verification.notice'));

    $applicant = User::factory()->create();
    $this->actingAs($applicant)->post(route('applications.store', $job), nativeApplicationPayload())->assertSessionHasNoErrors();
    $application = ApplicationForm::withoutGlobalScopes()->where('job_id', $job->id)->firstOrFail();
    $this->actingAs(User::factory()->create())->get(route('client.applications.show', $application))->assertForbidden();
});

test('native application requires every displayed field except middle name', function () {
    [$job] = nativeApplicationJob();

    $this->actingAs(User::factory()->create())->post(route('applications.store', $job), [
        'first_name' => 'Alice', 'last_name' => 'Applicant',
    ])->assertSessionHasErrors([
        'profile_image', 'email', 'phone', 'country_code', 'state', 'city', 'identification_type',
        'identification_document', 'identity_issuing_country_code', 'resume',
    ])->assertSessionDoesntHaveErrors(['middle_name', 'education_documents']);
    expect(ApplicationForm::withoutGlobalScopes()->count())->toBe(0);
});

test('required role-specific identity is explained and validated with its international issuer country', function () {
    [$job] = nativeApplicationJob([
        'requires_identity_document' => true,
        'identity_requirement_reason' => 'This regulated role requires identity verification.',
    ]);
    $applicant = User::factory()->create();

    $this->actingAs($applicant)->get(route('applications.create', $job))
        ->assertOk()
        ->assertSee('This regulated role requires identity verification.')
        ->assertSee('identity_issuing_country_code', false);

    $this->actingAs($applicant)->withHeaders(['Accept' => 'application/json'])
        ->post(route('applications.store', $job), collect(nativeApplicationPayload())
            ->except(['identification_type', 'identification_document', 'identity_issuing_country_code'])->all())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['identification_type', 'identification_document', 'identity_issuing_country_code']);

    expect(ApplicationForm::withoutGlobalScopes()->count())->toBe(0);
});

test('job screening questions are validated and stored as immutable application snapshots for recruiters', function () {
    [$job, $owner] = nativeApplicationJob();
    $shortText = nativeScreeningQuestion($job, [
        'type' => JobScreeningQuestionType::ShortText,
        'prompt' => 'Describe your most relevant experience.',
        'is_required' => true,
        'position' => 0,
    ]);
    $singleSelect = nativeScreeningQuestion($job, [
        'type' => JobScreeningQuestionType::SingleSelect,
        'prompt' => 'Which work arrangement can you support?',
        'options' => ['Remote', 'Hybrid'],
        'is_required' => true,
        'position' => 1,
    ]);
    $multiSelect = nativeScreeningQuestion($job, [
        'type' => JobScreeningQuestionType::MultiSelect,
        'prompt' => 'Which tools have you used?',
        'options' => ['PHP', 'Laravel', 'MySQL'],
        'is_required' => false,
        'position' => 2,
    ]);
    $yesNo = nativeScreeningQuestion($job, [
        'type' => JobScreeningQuestionType::YesNo,
        'prompt' => 'Can you start within one month?',
        'is_required' => true,
        'position' => 3,
    ]);
    $applicant = User::factory()->create();

    $page = $this->actingAs($applicant)->get(route('applications.create', $job))->assertOk()
        ->assertSee('Describe your most relevant experience.')
        ->assertSee('Which work arrangement can you support?')
        ->assertSee('Which tools have you used?')
        ->assertSee('Can you start within one month?');
    expect(substr_count($page->getContent(), 'data-wizard-step'))->toBe(5);

    $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('applications.store', $job), nativeApplicationPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'screening_answers.'.$shortText->question_key,
            'screening_answers.'.$singleSelect->question_key,
            'screening_answers.'.$multiSelect->question_key,
            'screening_answers.'.$yesNo->question_key,
        ]);

    $answers = [
        $shortText->question_key => 'Five years building Laravel applications.',
        $singleSelect->question_key => 'Hybrid',
        $multiSelect->question_key => ['PHP', 'Laravel'],
        $yesNo->question_key => 'yes',
    ];
    $this->post(route('applications.store', $job), [...nativeApplicationPayload(), 'screening_answers' => $answers])
        ->assertCreated();

    $application = ApplicationForm::withoutGlobalScopes()->where('job_id', $job->id)->firstOrFail();
    $snapshots = $application->screeningAnswers()->orderBy('position')->get();
    expect($snapshots)->toHaveCount(4)
        ->and($snapshots[0]->prompt)->toBe('Describe your most relevant experience.')
        ->and($snapshots[0]->answer)->toBe('Five years building Laravel applications.')
        ->and($snapshots[1]->answer)->toBe('Hybrid')
        ->and($snapshots[2]->answer)->toBe(['PHP', 'Laravel'])
        ->and($snapshots[2]->is_required)->toBeTrue()
        ->and($snapshots[3]->answer)->toBe('Yes');

    $shortText->update(['prompt' => 'An edited prompt after submission.']);
    $yesNo->delete();

    $this->actingAs($owner)->get(route('organisations.candidates.show', $application))
        ->assertOk()
        ->assertSee('Describe your most relevant experience.')
        ->assertSee('Five years building Laravel applications.')
        ->assertSee('Hybrid')
        ->assertSee('PHP, Laravel')
        ->assertDontSee('An edited prompt after submission.');
    expect($application->screeningAnswers()->where('question_key', $yesNo->question_key)->exists())->toBeTrue();
});

test('applicants cannot submit answers to another job or invalid configured choices', function () {
    [$job] = nativeApplicationJob();
    [$otherJob] = nativeApplicationJob(['title' => 'Other Role']);
    $question = nativeScreeningQuestion($job, [
        'type' => JobScreeningQuestionType::SingleSelect,
        'prompt' => 'Which work arrangement can you support?',
        'options' => ['Remote', 'Hybrid'],
        'is_required' => true,
    ]);
    $otherQuestion = nativeScreeningQuestion($otherJob, [
        'prompt' => 'Describe experience for another job.',
        'is_required' => false,
    ]);
    $applicant = User::factory()->create();

    $this->actingAs($applicant)->withHeaders(['Accept' => 'application/json'])
        ->post(route('applications.store', $job), [...nativeApplicationPayload(), 'screening_answers' => [
            $question->question_key => 'On-site',
        ]])->assertUnprocessable()->assertJsonValidationErrors('screening_answers.'.$question->question_key);

    $this->post(route('applications.store', $job), [...nativeApplicationPayload(), 'screening_answers' => [
        $question->question_key => 'Remote',
        $otherQuestion->question_key => 'Tampered response',
    ]])->assertUnprocessable()->assertJsonValidationErrors('screening_answers');

    expect(ApplicationForm::withoutGlobalScopes()->count())->toBe(0);
});

test('native application country names come from the selected country rather than submitted text', function () {
    [$job] = nativeApplicationJob();
    $payload = nativeApplicationPayload();
    $payload['country_code'] = ' ng ';
    $payload['country'] = 'Spoofed country name';

    $this->actingAs(User::factory()->create())->post(route('applications.store', $job), $payload)->assertSessionHasNoErrors();
    $application = ApplicationForm::withoutGlobalScopes()->where('job_id', $job->id)->firstOrFail();
    expect($application->country_code)->toBe('NG')->and($application->country)->toBe('Nigeria');
});
