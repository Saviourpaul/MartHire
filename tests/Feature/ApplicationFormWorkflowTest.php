<?php

use App\Enums\ApplicationDocumentType;
use App\Enums\ApplicationStatus;
use App\Models\ApplicationDocument;
use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['locations.cache.path' => sys_get_temp_dir().'/marthire-locations-'.bin2hex(random_bytes(8))]);
    config(['locations.cache.lock_path' => config('locations.cache.path')]);
    Http::fake(['*' => Http::response([], 503)]);
});

function validApplicationPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Ada',
        'middle_name' => 'M',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'phone' => '+2348012345678',
        'country_code' => 'NG',
        'date_of_birth' => '1995-01-01',
        'gender' => 'female',
        'marital_status' => 'single',
        'state' => 'Lagos',
        'city' => 'Ikeja',
        'address' => '12 Market Road',
        'zipcode' => '100001',
        'identification_type' => ApplicationDocumentType::NationalIdentityCard->value,
        'identification_document' => UploadedFile::fake()->create('national-identity-card.pdf', 100, 'application/pdf'),
        'education_documents' => [
            [
                'type' => 'bsc',
                'file' => UploadedFile::fake()->create('degree.pdf', 100, 'application/pdf'),
            ],
            [
                'type' => 'nysc',
                'file' => UploadedFile::fake()->create('nysc.pdf', 100, 'application/pdf'),
            ],
        ],
        ...$overrides,
    ];
}

function tinyPngUpload(string $name = 'tiny-profile.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
    );
}

it('renders the application wizard with dependent location and document controls', function () {
    $employer = User::factory()->employer()->create();
    $job = Job::factory()->approved()->for($employer, 'employer')->create();
    $applicant = User::factory()->applicant()->create();

    $this->actingAs($applicant)
        ->get(route('applications.create', $job))
        ->assertOk()
        ->assertSee('Personal details')
        ->assertSee('Identification')
        ->assertSee('Education')
        ->assertSee('Review')
        ->assertSee('data-wizard-step="0"', false)
        ->assertSee('data-wizard-step="1"', false)
        ->assertSee('data-wizard-step="2"', false)
        ->assertSee('data-wizard-step="3"', false)
        ->assertSeeInOrder([
            'data-wizard-step="1"',
            'hidden',
            'data-wizard-step="2"',
            'hidden',
            'data-wizard-step="3"',
            'hidden',
        ], false)
        ->assertSee('data-overall-progress', false)
        ->assertSee('application-wizard', false)
        ->assertSee('data-state-of-origin', false)
        ->assertSee('data-local-government-area', false)
        ->assertSee('data-lga-url="/locations/states/', false)
        ->assertDontSee('data-lga-url="http', false)
        ->assertSee('id="profile-image-preview"', false)
        ->assertSee('data-profile-image-trigger', false)
        ->assertSee('data-remove-profile-image', false)
        ->assertSee('data-file-kind="profile-image"', false)
        ->assertSee('data-min-width="200"', false)
        ->assertSee('name="identification_type"', false)
        ->assertSee('name="identification_document"', false)
        ->assertSee('data-identification-document-dropzone', false)
        ->assertSee('aria-disabled="true"', false)
        ->assertSee('National Identity Card / NIN Slip')
        ->assertSee('International Passport')
        ->assertSee("Driver's License")
        ->assertSee("Voter's Card")
        ->assertDontSee('Bank Verification Number')
        ->assertDontSee('NIN number')
        ->assertSee('Choose a photo to preview it before submission.')
        ->assertSee('Add another document');
});

it('redirects guest applicants to sign in before an application can be started', function () {
    $employer = User::factory()->employer()->create();
    $job = Job::factory()->approved()->for($employer, 'employer')->create();

    $this->get(route('job-details', $job))
        ->assertOk()
        ->assertSee(route('applications.create', $job), false);

    $this->get(route('applications.create', $job))
        ->assertRedirect(route('login'))
        ->assertSessionHas('url.intended', route('applications.create', $job));
});

it('stores applications, synchronizes applicant profile, and prevents duplicate applications', function () {
    Storage::fake('public');
    Storage::fake('local');

    $employer = User::factory()->employer()->create();
    $job = Job::factory()->approved()->for($employer, 'employer')->create();
    $applicant = User::factory()->applicant()->create([
        'first_name' => 'Old',
        'last_name' => 'Name',
        'phone' => null,
        'profile_image_path' => 'profile-images/existing.jpg',
    ]);

    $this->actingAs($applicant)
        ->post(route('applications.store', $job), validApplicationPayload())
        ->assertRedirect()
        ->assertSessionHas('success', 'Your application has been submitted successfully.');

    $application = ApplicationForm::query()->firstOrFail();
    $identityDocument = $application->documents()
        ->where('document_type', ApplicationDocumentType::NationalIdentityCard->value)
        ->firstOrFail();
    $identification = $applicant->identificationDocument()->firstOrFail();

    expect($application->job_id)->toBe($job->id)
        ->and($application->user_id)->toBe($applicant->id)
        ->and($application->status)->toBe(CandidatePipelineStage::Submitted)
        ->and($application->documents)->toHaveCount(3)
        ->and($application->statusHistories)->toHaveCount(1);

    expect($identification->document_type)->toBe(ApplicationDocumentType::NationalIdentityCard)
        ->and($identityDocument->identificationDocument->is($identification))->toBeTrue()
        ->and($identityDocument->file_path)->toBe($identification->file_path);

    $applicant->refresh();

    expect($applicant->first_name)->toBe('Ada')
        ->and($applicant->last_name)->toBe('Lovelace')
        ->and($applicant->phone)->toBe('+2348012345678')
        ->and($applicant->country)->toBe('Nigeria')
        ->and($applicant->state)->toBe('Lagos')
        ->and($applicant->city)->toBe('Ikeja')
        ->and($applicant->profile_image_path)->toBe('profile-images/existing.jpg');

    $this->assertDatabaseHas('application_forms', [
        'job_id' => $job->id,
        'user_id' => $applicant->id,
        'email' => 'ada@example.com',
    ]);

    Storage::disk('local')->assertExists($identityDocument->file_path);

    $this->actingAs($applicant)
        ->get(route('application-documents.download', $identityDocument))
        ->assertOk();

    $this->actingAs($employer)
        ->get(route('application-documents.download', $identityDocument))
        ->assertOk();

    $this->actingAs(User::factory()->applicant()->create())
        ->get(route('application-documents.download', $identityDocument))
        ->assertForbidden();

    $duplicatePayload = validApplicationPayload();
    unset($duplicatePayload['profile_image']);

    $this->actingAs($applicant)
        ->from(route('applications.create', $job))
        ->post(route('applications.store', $job), $duplicatePayload)
        ->assertRedirect(route('applications.create', $job))
        ->assertSessionHasErrors('job');

    expect(ApplicationForm::count())->toBe(1);
});

it('requires a supported identification method and its document', function () {
    $employer = User::factory()->employer()->create();
    $job = Job::factory()->approved()->for($employer, 'employer')->create();
    $applicant = User::factory()->applicant()->create();

    $this->actingAs($applicant)
        ->from(route('applications.create', $job))
        ->post(route('applications.store', $job), validApplicationPayload([
            'identification_type' => 'bank_verification_number',
            'identification_document' => null,
        ]))
        ->assertRedirect(route('applications.create', $job))
        ->assertSessionHasErrors(['identification_type', 'identification_document']);

    expect(ApplicationForm::count())->toBe(0);
});

it('returns applicants to the identity step after identity validation fails', function () {
    $employer = User::factory()->employer()->create();
    $job = Job::factory()->approved()->for($employer, 'employer')->create();
    $applicant = User::factory()->applicant()->create([
        'profile_image_path' => 'profile-images/existing.jpg',
    ]);

    $this->actingAs($applicant)
        ->from(route('applications.create', $job))
        ->post(route('applications.store', $job), validApplicationPayload([
            'identification_document' => null,
        ]))
        ->assertRedirect(route('applications.create', $job))
        ->assertSessionHasErrors('identification_document');

    $this->actingAs($applicant)
        ->get(route('applications.create', $job))
        ->assertOk()
        ->assertSee('data-initial-step="1"', false)
        ->assertSee('data-wizard-step="0"', false)
        ->assertSee('data-wizard-step="1"', false);
});

it('validates profile photo and document uploads before storing an application', function () {
    Storage::fake('public');
    Storage::fake('local');

    $employer = User::factory()->employer()->create();
    $job = Job::factory()->approved()->for($employer, 'employer')->create();
    $applicant = User::factory()->applicant()->create();

    $this->actingAs($applicant)
        ->from(route('applications.create', $job))
        ->post(route('applications.store', $job), validApplicationPayload([
            'profile_image' => tinyPngUpload(),
            'identification_document' => UploadedFile::fake()->create('identity.svg', 100, 'image/svg+xml'),
            'education_documents' => [
                [
                    'type' => 'bsc',
                    'file' => UploadedFile::fake()->create('degree.exe', 100, 'application/octet-stream'),
                ],
            ],
        ]))
        ->assertRedirect(route('applications.create', $job))
        ->assertSessionHasErrors([
            'profile_image',
            'identification_document',
            'education_documents.0.file',
        ]);

    expect(ApplicationForm::count())->toBe(0);
});

it('lets only the owning employer move a candidate through the pipeline', function () {
    $owner = User::factory()->employer()->create();
    $otherEmployer = User::factory()->employer()->create();
    $applicant = User::factory()->applicant()->create();
    $job = Job::factory()->for($owner, 'employer')->create();
    $application = ApplicationForm::factory()
        ->for($job, 'job')
        ->for($applicant, 'applicant')
        ->create();

    $this->actingAs($otherEmployer)
        ->patch(route('employer.applications.pipeline.move', $application), [
            'stage' => 'shortlisted',
            'remarks' => 'Looks good.',
        ])
        ->assertForbidden();

    $this->actingAs($owner)
        ->patch(route('employer.applications.pipeline.move', $application), [
            'stage' => 'shortlisted',
            'remarks' => 'Looks good.',
        ])
        ->assertRedirect();

    $application->refresh();

    expect($application->status)->toBe(CandidatePipelineStage::Shortlisted)
        ->and($application->reviewed_by)->toBe($owner->id)
        ->and($application->statusHistories()->count())->toBe(1);

    $this->actingAs($applicant)
        ->get(route('client.applications.show', $application))
        ->assertOk()
        ->assertSee('Shortlisted');
});

it('does not expose a separate document review endpoint', function () {
    expect(Route::has('employer.application-documents.review'))->toBeFalse();
});
