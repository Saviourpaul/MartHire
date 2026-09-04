<?php

use App\Enums\ApplicationDocumentType;
use App\Enums\ApplicationStatus;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use Database\Seeders\NigeriaLocationSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(NigeriaLocationSeeder::class);
});

function validApplicationPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Ada',
        'middle_name' => 'M',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'phone' => '+2348012345678',
        'nationality' => 'Nigeria',
        'date_of_birth' => '1995-01-01',
        'gender' => 'female',
        'marital_status' => 'single',
        'state_of_origin' => 'Lagos',
        'local_government_area' => 'Ikeja',
        'address' => '12 Market Road',
        'nin_number' => '12345678901',
        'nin_document' => UploadedFile::fake()->create('nin.pdf', 100, 'application/pdf'),
        'bvn_number' => '22345678901',
        'bvn_document' => UploadedFile::fake()->create('bvn.pdf', 100, 'application/pdf'),
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
        ->assertSee('Personal Information')
        ->assertSee('Identification')
        ->assertSee('Educational Qualification')
        ->assertSee('Application Summary')
        ->assertSee('data-state-of-origin', false)
        ->assertSee('data-local-government-area', false)
        ->assertSee('data-lga-url=', false)
        ->assertSee('id="profile-image-preview"', false)
        ->assertSee('data-file-kind="profile-image"', false)
        ->assertSee('data-min-width="200"', false)
        ->assertSee('Choose a photo to preview it before submission.')
        ->assertSee('Add another document');
});

it('redirects guest applicants to registration and resumes the intended application after registration', function () {
    $employer = User::factory()->employer()->create();
    $job = Job::factory()->approved()->for($employer, 'employer')->create();

    $this->get(route('job-details', $job))
        ->assertOk()
        ->assertSee(route('applications.create', $job), false);

    $this->get(route('applications.create', $job))
        ->assertRedirect(route('register'))
        ->assertSessionHas('url.intended', route('applications.create', $job));

    $this->post(route('register'), [
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'username' => 'grace-hopper',
        'email' => 'grace@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertRedirect(route('applications.create', $job));

    $this->assertAuthenticated();

    $this->get(route('applications.create', $job))
        ->assertOk()
        ->assertSee('Apply for '.$job->title);
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
    $document = $application->documents()->firstOrFail();

    expect($application->job_id)->toBe($job->id)
        ->and($application->user_id)->toBe($applicant->id)
        ->and($application->status)->toBe(ApplicationStatus::Pending)
        ->and($application->documents)->toHaveCount(4)
        ->and($application->statusHistories)->toHaveCount(1);

    $applicant->refresh();

    expect($applicant->first_name)->toBe('Ada')
        ->and($applicant->last_name)->toBe('Lovelace')
        ->and($applicant->phone)->toBe('+2348012345678')
        ->and($applicant->nationality)->toBe('Nigeria')
        ->and($applicant->state_of_origin)->toBe('Lagos')
        ->and($applicant->local_government_area)->toBe('Ikeja')
        ->and($applicant->profile_image_path)->toBe('profile-images/existing.jpg');

    $this->assertDatabaseHas('application_forms', [
        'job_id' => $job->id,
        'user_id' => $applicant->id,
        'email' => 'ada@example.com',
    ]);

    Storage::disk('local')->assertExists($document->file_path);

    $this->actingAs($applicant)
        ->get(route('application-documents.download', $document))
        ->assertOk();

    $this->actingAs($employer)
        ->get(route('application-documents.download', $document))
        ->assertOk();

    $this->actingAs(User::factory()->applicant()->create())
        ->get(route('application-documents.download', $document))
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

it('requires nin and bvn numbers to be exactly eleven numeric digits', function () {
    $employer = User::factory()->employer()->create();
    $job = Job::factory()->approved()->for($employer, 'employer')->create();
    $applicant = User::factory()->applicant()->create();

    $this->actingAs($applicant)
        ->from(route('applications.create', $job))
        ->post(route('applications.store', $job), validApplicationPayload([
            'nin_number' => '1234567890',
            'bvn_number' => '223456789012',
        ]))
        ->assertRedirect(route('applications.create', $job))
        ->assertSessionHasErrors(['nin_number', 'bvn_number']);

    $this->actingAs($applicant)
        ->from(route('applications.create', $job))
        ->post(route('applications.store', $job), validApplicationPayload([
            'nin_number' => '1234567890A',
            'bvn_number' => '2234567890B',
        ]))
        ->assertRedirect(route('applications.create', $job))
        ->assertSessionHasErrors(['nin_number', 'bvn_number']);

    expect(ApplicationForm::count())->toBe(0);
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
            'nin_document' => UploadedFile::fake()->create('nin.svg', 100, 'image/svg+xml'),
            'bvn_document' => UploadedFile::fake()->create('bvn.pdf', 6000, 'application/pdf'),
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
            'nin_document',
            'bvn_document',
            'education_documents.0.file',
        ]);

    expect(ApplicationForm::count())->toBe(0);
});

it('lets only the owning employer review applications and notifies the applicant', function () {
    $owner = User::factory()->employer()->create();
    $otherEmployer = User::factory()->employer()->create();
    $applicant = User::factory()->applicant()->create();
    $job = Job::factory()->for($owner, 'employer')->create();
    $application = ApplicationForm::factory()
        ->for($job, 'job')
        ->for($applicant, 'applicant')
        ->create();

    $this->actingAs($otherEmployer)
        ->patch(route('employer.applications.review', $application), [
            'status' => 'approved',
            'remarks' => 'Looks good.',
        ])
        ->assertForbidden();

    $this->actingAs($owner)
        ->patch(route('employer.applications.review', $application), [
            'status' => 'approved',
            'remarks' => 'Looks good.',
        ])
        ->assertRedirect();

    $application->refresh();

    expect($application->status)->toBe(ApplicationStatus::Approved)
        ->and($application->reviewed_by)->toBe($owner->id)
        ->and($application->statusHistories()->count())->toBe(1);

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $applicant->id,
        'notifiable_type' => User::class,
    ]);

    $this->actingAs($applicant)
        ->get(route('client.jobs'))
        ->assertOk()
        ->assertSee('Approved');
});

it('tracks document review status separately and enforces ownership', function () {
    $owner = User::factory()->employer()->create();
    $otherEmployer = User::factory()->employer()->create();
    $applicant = User::factory()->applicant()->create();
    $job = Job::factory()->for($owner, 'employer')->create();
    $application = ApplicationForm::factory()
        ->for($job, 'job')
        ->for($applicant, 'applicant')
        ->create();
    $document = ApplicationDocument::factory()
        ->for($application, 'applicationForm')
        ->type(ApplicationDocumentType::Nin)
        ->create();

    $this->actingAs($otherEmployer)
        ->patch(route('employer.application-documents.review', $document), [
            'status' => 'rejected',
            'remarks' => 'Unreadable.',
        ])
        ->assertForbidden();

    $this->actingAs($owner)
        ->patch(route('employer.application-documents.review', $document), [
            'status' => 'rejected',
            'remarks' => 'Unreadable.',
        ])
        ->assertRedirect();

    $document->refresh();

    expect($document->status)->toBe(ApplicationStatus::Rejected)
        ->and($document->reviewed_by)->toBe($owner->id)
        ->and($document->statusHistories()->count())->toBe(1);

    $this->actingAs($applicant)
        ->get(route('client.documents'))
        ->assertOk()
        ->assertSee('Rejected')
        ->assertSee('Unreadable.');
});
