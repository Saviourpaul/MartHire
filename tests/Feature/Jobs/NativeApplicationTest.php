<?php

use App\Enums\JobStatus;
use App\Enums\ApplicationDocumentType;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\CandidateJobPreference;
use App\Models\User;
use App\Models\UserIdentificationDocument;
use App\Services\ApplicantProfileChecklistService;
use App\Support\OrganizationContext;
use Illuminate\Http\UploadedFile;
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

function nativeApplicationPayload(): array
{
    return [
        'first_name' => 'Alice',
        'last_name' => 'Applicant',
        'email' => 'alice@example.test',
        'phone' => '+2348012345678',
        'date_of_birth' => '1995-05-10',
        'gender' => 'female',
        'marital_status' => 'single',
        'country_code' => 'NG',
        'country' => 'Nigeria',
        'state' => 'Lagos',
        'local_government_area' => 'Ikeja',
        'address' => '10 Example Avenue',
        'profile_image' => UploadedFile::fake()->createWithContent('profile.png', file_get_contents(base_path('tests/Fixtures/applications/profile.png'))),
        'identification_type' => 'national_identity_card',
        'identification_document' => UploadedFile::fake()->create('identification.pdf', 10, 'application/pdf'),
        'resume' => UploadedFile::fake()->create('resume.pdf', 10, 'application/pdf'),
        'education_documents' => [
            ['name' => 'Degree certificate', 'file' => UploadedFile::fake()->create('degree.pdf', 10, 'application/pdf')],
        ],
        'profile_contact' => [
            'professional_headline' => 'Experienced application engineer',
            'about' => 'I build reliable web applications and enjoy solving complex problems.',
            'disability_status' => 'no',
        ],
        'profile_employment' => [
            'company' => 'Example Company',
            'title' => 'Application Engineer',
            'start_date' => '2022-01-01',
            'end_date' => '2024-12-31',
            'is_current' => '1',
        ],
        'profile_skills' => ['PHP', 'Laravel'],
        'profile_education' => [
            'institution' => 'Example University',
            'qualification' => 'BSc Computer Science',
        ],
        'profile_links' => ['linkedin' => 'https://www.linkedin.com/in/alice-example'],
        'profile_preferences' => ['preferred_roles' => ['Software Engineer']],
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
        ->assertSee('identification_document', false)
        ->assertSee('education_documents[', false)
        ->assertSee('Submit application');
});

test('the native application includes a profile completion step and shared location lookups for an incomplete profile', function () {
    [$job] = nativeApplicationJob();
    $response = $this->actingAs(User::factory()->create())->get(route('applications.create', $job))->assertOk()
        ->assertSee('nativeApplicationForm', false)
        ->assertSee('data-step-title="Your address"', false)
        ->assertSee('profile_employment[is_current]', false)
        ->assertDontSee('profile_contact[gender_identity]', false)
        ->assertSee('Review and submit')
        ->assertDontSee('async loadStates', false);

    expect(substr_count($response->getContent(), 'data-wizard-step'))->toBe(5)
        ->and(substr_count($response->getContent(), 'name="gender"'))->toBe(1);
    expect(route('locations.states', absolute: false))->toBe('/lookups/locations/states');
    expect(route('locations.cities', absolute: false))->toBe('/lookups/locations/cities');
});

test('a complete applicant profile is carried into the application without a profile completion step', function () {
    [$job] = nativeApplicationJob();
    $applicant = completeNativeApplicant();

    $response = $this->actingAs($applicant)->get(route('applications.create', $job))->assertOk();

    expect(substr_count($response->getContent(), 'data-wizard-step'))->toBe(4)
        ->and($response->viewData('applicantProfile')->professional_headline)->toBe('Experienced application engineer')
        ->and($response->viewData('profileRequirements')['has_additional_fields'])->toBeFalse();
    $response->assertSee('saved-resume.pdf')->assertSee('saved-identity.pdf');
});

test('saved résumé and identification are reused while application documents remain private snapshots', function () {
    [$job] = nativeApplicationJob();
    $applicant = completeNativeApplicant();
    $payload = nativeApplicationPayload();
    unset(
        $payload['profile_image'],
        $payload['identification_document'],
        $payload['resume'],
        $payload['profile_contact'],
        $payload['profile_employment'],
        $payload['profile_skills'],
        $payload['profile_education'],
        $payload['profile_links'],
        $payload['profile_preferences']
    );

    $this->actingAs($applicant)->withHeaders(['Accept' => 'application/json'])
        ->post(route('applications.store', $job), $payload)
        ->assertCreated();

    $application = ApplicationForm::withoutGlobalScopes()->where('job_id', $job->id)->firstOrFail();
    $documents = $application->documents()->get();
    $resume = $documents->firstWhere('document_type', ApplicationDocumentType::Resume);
    $identity = $documents->firstWhere('document_type', ApplicationDocumentType::NationalIdentityCard);

    expect($documents)->toHaveCount(3)
        ->and($resume)->not->toBeNull()
        ->and($resume->file_path)->not->toBe('candidate-resumes/saved-resume.pdf')
        ->and($identity)->not->toBeNull()
        ->and($identity->file_path)->toBe('user-identification-documents/'.$applicant->id.'/identity.pdf')
        ->and($applicant->applicantProfile()->first()->employments()->count())->toBe(1);
    Storage::disk('local')->assertExists($resume->file_path);
    Storage::disk('local')->assertExists($identity->file_path);
});

test('disability details are required only when the applicant answers yes and are saved to the profile', function () {
    [$job] = nativeApplicationJob();
    $applicant = User::factory()->create();
    $payload = nativeApplicationPayload();
    $payload['profile_contact']['disability_status'] = 'yes';

    $this->actingAs($applicant)->withHeaders(['Accept' => 'application/json'])
        ->post(route('applications.store', $job), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('profile_contact.disability_details');
    expect(ApplicationForm::withoutGlobalScopes()->count())->toBe(0);

    $payload = nativeApplicationPayload();
    $payload['profile_contact']['disability_status'] = 'yes';
    $payload['profile_contact']['disability_details'] = 'Uses a screen reader';
    $this->post(route('applications.store', $job), $payload)->assertCreated();

    expect($applicant->applicantProfile()->firstOrFail()->selfIdentification->disability_details)
        ->toBe('Uses a screen reader');
});

test('the native wizard receives a JSON receipt destination without changing private storage', function () {
    [$job] = nativeApplicationJob();
    $applicant = User::factory()->create();
    $response = $this->actingAs($applicant)->withHeaders(['Accept' => 'application/json'])
        ->post(route('applications.store', $job), nativeApplicationPayload());
    $application = ApplicationForm::withoutGlobalScopes()->where('job_id', $job->id)->firstOrFail();

    $response->assertCreated()->assertJsonPath('submitted', true)
        ->assertJsonPath('redirect', route('client.applications.show', $application));
    expect($application->documents)->toHaveCount(3);
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
            'last_name', 'state', 'resume', 'profile_contact.professional_headline',
            'profile_employment.company', 'profile_skills', 'profile_education.institution',
            'profile_links.linkedin', 'profile_preferences.preferred_roles',
        ]);

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

    $response = $this->actingAs($applicant)->post(route('applications.store', $job), nativeApplicationPayload());
    $response->assertSessionHasNoErrors();

    $application = ApplicationForm::withoutGlobalScopes()->where('job_id', $job->id)->firstOrFail();
    $response->assertRedirect(route('client.applications.show', $application));
    expect($application->user_id)->toBe($applicant->id)
        ->and($application->organization_id)->toBe($job->organization_id)
        ->and($application->email)->toBe('alice@example.test')
        ->and($application->status->value)->toBe('submitted')
        ->and($application->statusHistories()->count())->toBe(1);

    $documents = ApplicationDocument::withoutGlobalScopes()->where('application_form_id', $application->id)->get();
    expect($documents)->toHaveCount(3);
    foreach ($documents as $document) {
        expect($document->organization_id)->toBe($job->organization_id);
        Storage::disk('local')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($document->file_path);
    }
    $profile = $applicant->applicantProfile()->with(['employments', 'educations', 'skills', 'links', 'selfIdentification'])->firstOrFail();
    $checklist = app(ApplicantProfileChecklistService::class)->forUser($applicant->fresh());
    expect($profile->professional_headline)->toBe('Experienced application engineer')
        ->and($profile->selfIdentification->gender_identity)->toBe('female')
        ->and($profile->selfIdentification->disability_status)->toBe('no')
        ->and($profile->employments)->toHaveCount(1)
        ->and($profile->employments->first()->is_current)->toBeTrue()
        ->and($profile->employments->first()->end_date)->toBeNull()
        ->and($profile->educations)->toHaveCount(1)
        ->and($profile->skills)->toHaveCount(2)
        ->and($profile->links)->toHaveCount(1)
        ->and($checklist['completed'])->toBe($checklist['total']);
    Storage::disk('public')->assertExists($applicant->fresh()->profile_image_path);

    $this->get(route('client.applications.show', $application))
        ->assertOk()->assertSee($application->reference)->assertSee('Documents received')
        ->assertSee('Résumé / CV')->assertDontSee('resume.pdf');

    $this->actingAs($owner)->get(route('organisations.candidates.index'))
        ->assertOk()->assertSee('Alice Applicant')
        ->assertSee(route('organisations.candidates.show', $application), false);
    $this->get(route('organisations.candidates.show', $application))
        ->assertOk()->assertSee($application->reference)->assertSee('Alice Applicant');
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

test('native application validation preserves required identity resume and education documents', function () {
    [$job] = nativeApplicationJob();

    $this->actingAs(User::factory()->create())->post(route('applications.store', $job), [
        'first_name' => 'Alice', 'last_name' => 'Applicant',
    ])->assertSessionHasErrors(['profile_image', 'identification_document', 'resume', 'education_documents']);
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
