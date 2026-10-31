<?php

use App\Jobs\Workable\SubmitJobApplicationJob;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function fakeApplicationForm(?array $form = null): void
{
    Http::fake([
        '*/spi/v3/jobs/SENIO001/application_form*' => Http::response(
            $form ?? json_decode(file_get_contents(base_path('tests/Fixtures/workable/application_form.json')), true),
            200,
        ),
    ]);
}

function nativePosting(): JobPosting
{
    $company = Company::factory()->create([
        'subdomain' => 'pilotco', 'api_token' => 'tok_123',
        'apply_mode' => 'native', 'credentials_status' => 'valid',
    ]);

    return JobPosting::factory()->for($company)->create([
        'shortcode' => 'SENIO001', 'status' => 'open', 'apply_supported' => true,
    ]);
}

/** The field names below are exactly what the Apply blade posts. */
function bladePayload(): array
{
    return [
        'firstname' => 'Cand', 'lastname' => 'Idate', 'email' => 'cand@example.com',
        'resume' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'),
        'answers' => [
            'q_free' => 'because', 'q_bool' => '1', 'q_drop' => 'c1', 'q_mc' => ['s1'],
        ],
        'confirm' => '1',
    ];
}

it('serves the native form fed with the live schema questions', function () {
    fakeApplicationForm();
    nativePosting();

    $response = $this->actingAs(User::factory()->create())->get('/workable-jobs/SENIO001/apply');

    $response->assertOk();
    $response->assertViewHas('form');
    $form = $response->viewData('form');
    expect(collect($form['questions'])->pluck('id'))->toContain('q_free', 'q_bool', 'q_mc');
    expect(collect($form['fields'])->pluck('key'))->toContain('firstname', 'lastname', 'resume');
    $response->assertSee('data-step-title="About you"', false)
        ->assertSee('data-step-title="CV and supporting details"', false)
        ->assertSee('data-step-title="Employer questions"', false)
        ->assertSee('data-step-title="Review and send"', false)
        ->assertSee('data-error-key="first_name"', false)
        ->assertSee('data-error-key="question_q_free"', false);
    expect(substr_count($response->getContent(), 'data-wizard-step'))->toBe(4);
    expect(collect($form['fields'])->firstWhere('key', 'resume')['max_file_size'])->toBe(5 * 1024 * 1024);
});

it('queues a native application from the blade field names, stores the resume privately, dispatches the job', function () {
    Bus::fake();
    Storage::fake('local');
    fakeApplicationForm();
    $posting = nativePosting();

    $response = $this->actingAs(User::factory()->create())
        ->post('/workable-jobs/SENIO001/apply', bladePayload());

    $response->assertRedirect(route('workable-jobs.apply', 'SENIO001'));
    $response->assertSessionHas('applied', true);

    $application = JobApplication::first();
    expect($application)->not->toBeNull();
    expect($application->status)->toBe('queued');
    expect($application->email)->toBe('cand@example.com');

    // Posted names are normalised to the canonical schema keys.
    expect($application->payload['data']['first_name'])->toBe('Cand');
    expect($application->payload['data']['last_name'])->toBe('Idate');
    expect($application->payload['data']['question_q_free'])->toBe('because');
    expect($application->payload['data'])->not->toHaveKey('firstname');
    expect($application->payload['data'])->not->toHaveKey('confirm');

    // Resume lives on the private disk, only path+name in the payload.
    expect($application->payload['files']['resume']['path'])->toContain("applications/{$posting->id}/");
    Storage::disk('local')->assertExists($application->payload['files']['resume']['path']);

    Bus::assertDispatched(SubmitJobApplicationJob::class, fn ($job) => $job->application->is($application));
});

it('rejects a submission with the consent box unticked', function () {
    Bus::fake();
    Storage::fake('local');
    fakeApplicationForm();
    nativePosting();

    $payload = bladePayload();
    unset($payload['confirm']);

    $response = $this->actingAs(User::factory()->create())
        ->from('/workable-jobs/SENIO001/apply')->post('/workable-jobs/SENIO001/apply', $payload);

    $response->assertSessionHasErrors('confirm');
    expect(JobApplication::count())->toBe(0);
    Bus::assertNotDispatched(SubmitJobApplicationJob::class);
});

it('rejects an invalid submission without creating an application', function () {
    Bus::fake();
    fakeApplicationForm();
    nativePosting();

    $response = $this->actingAs(User::factory()->create())
        ->from('/workable-jobs/SENIO001/apply')
        ->post('/workable-jobs/SENIO001/apply', ['firstname' => 'Cand']); // missing almost everything

    $response->assertSessionHasErrors(['last_name', 'email', 'resume']);
    expect(JobApplication::count())->toBe(0);
    Bus::assertNotDispatched(SubmitJobApplicationJob::class);
});

it('redirects a redirect-mode employer to the hosted form without creating an application', function () {
    Bus::fake();

    $company = Company::factory()->create(['apply_mode' => 'redirect']);
    JobPosting::factory()->for($company)->create([
        'shortcode' => 'REDIR001', 'status' => 'open',
        'application_url' => 'https://pilotco.workable.com/j/REDIR001',
    ]);

    $response = $this->actingAs(User::factory()->create())->post('/workable-jobs/REDIR001/apply', [
        'firstname' => 'X', 'lastname' => 'Y', 'email' => 'x@example.com', 'confirm' => '1',
    ]);

    $response->assertRedirect('https://pilotco.workable.com/j/REDIR001');
    expect(JobApplication::count())->toBe(0);
    Bus::assertNotDispatched(SubmitJobApplicationJob::class);
});

it('requires authentication to submit', function () {
    nativePosting();

    $this->post('/workable-jobs/SENIO001/apply', ['email' => 'x@example.com'])->assertRedirect('/login');
    expect(JobApplication::count())->toBe(0);
});

it('normalizes Workable phone numbers and retains split parts outside the provider payload', function () {
    Bus::fake();
    Storage::fake('local');
    fakeApplicationForm();
    nativePosting();

    $this->actingAs(User::factory()->create())->withHeaders(['Accept' => 'application/json'])
        ->post('/workable-jobs/SENIO001/apply', [...bladePayload(), 'phone' => '020 7946 0018', 'phone_country' => 'GB', 'phone_dial_code' => '+999'])
        ->assertCreated();

    $application = JobApplication::firstOrFail();
    expect($application->payload['data']['phone'])->toBe('+442079460018')
        ->and($application->payload['data'])->not->toHaveKey('phone_country')
        ->and($application->payload['phone'])->toBe([
            'phone_country' => 'GB', 'phone_dial_code' => '+44', 'phone_national_number' => '2079460018',
        ]);
});

it('rejects invalid optional Workable phone numbers when a number is provided', function () {
    Bus::fake();
    fakeApplicationForm();
    nativePosting();
    $this->actingAs(User::factory()->create())->withHeaders(['Accept' => 'application/json'])
        ->post('/workable-jobs/SENIO001/apply', [...bladePayload(), 'phone' => '+442079460018', 'phone_country' => 'US'])
        ->assertUnprocessable()->assertJsonValidationErrors('phone');
    expect(JobApplication::count())->toBe(0);
});

it('accepts a multipart wizard submission as JSON without redirecting or changing the private pipeline', function () {
    Bus::fake();
    Storage::fake('local');
    fakeApplicationForm();
    nativePosting();

    $this->actingAs(User::factory()->create())
        ->post(route('workable-jobs.apply.store', 'SENIO001'), bladePayload(), ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJson(['submitted' => true])
        ->assertJsonMissingPath('redirect');

    $application = JobApplication::sole();
    expect($application->status)->toBe('queued')
        ->and($application->payload['data']['first_name'])->toBe('Cand');
    Storage::disk('local')->assertExists($application->payload['files']['resume']['path']);
    Bus::assertDispatched(SubmitJobApplicationJob::class, fn ($job) => $job->application->is($application));
});

it('returns canonical inline validation errors to the wizard without creating an application', function () {
    Bus::fake();
    fakeApplicationForm();
    nativePosting();

    $this->actingAs(User::factory()->create())
        ->postJson(route('workable-jobs.apply.store', 'SENIO001'), ['firstname' => 'Cand'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['last_name', 'email', 'resume', 'question_q_free', 'question_q_bool', 'confirm']);

    expect(JobApplication::count())->toBe(0);
    Bus::assertNotDispatched(SubmitJobApplicationJob::class);
});

it('returns a hosted destination as JSON instead of following a cross origin redirect', function () {
    Bus::fake();
    $company = Company::factory()->create(['apply_mode' => 'redirect']);
    $posting = JobPosting::factory()->for($company)->create([
        'shortcode' => 'REDIRJSON',
        'status' => 'open',
        'application_url' => 'https://employer.workable.com/j/REDIRJSON',
    ]);

    $this->actingAs(User::factory()->create())
        ->postJson(route('workable-jobs.apply.store', $posting->shortcode), [])
        ->assertConflict()
        ->assertJson(['redirect' => $posting->application_url]);

    expect(JobApplication::count())->toBe(0);
    Bus::assertNotDispatched(SubmitJobApplicationJob::class);
});

it('omits an empty questions step and unsupported optional questions', function () {
    $form = json_decode(file_get_contents(base_path('tests/Fixtures/workable/application_form.json')), true);
    $form['questions'] = [['id' => 'optional_complex', 'body' => 'Unsupported optional address', 'type' => 'complex', 'required' => false]];
    fakeApplicationForm($form);
    nativePosting();

    $response = $this->actingAs(User::factory()->create())
        ->get(route('workable-jobs.apply', 'SENIO001'))->assertOk()
        ->assertDontSee('data-step-title="Employer questions"', false)
        ->assertDontSee('Unsupported optional address');

    expect($response->viewData('form')['questions'])->toBe([])
        ->and(substr_count($response->getContent(), 'data-wizard-step'))->toBe(3);
});

it('restores entered answers after a non JavaScript validation redirect and explains file reselection', function () {
    Bus::fake();
    fakeApplicationForm();
    nativePosting();
    $url = route('workable-jobs.apply', 'SENIO001');

    $this->actingAs(User::factory()->create())->from($url)
        ->post(route('workable-jobs.apply.store', 'SENIO001'), [
            'firstname' => 'Retained Candidate', 'lastname' => 'Family', 'email' => 'invalid-email',
            'cover_letter' => 'Retained cover letter',
            'answers' => ['q_free' => 'Remember this answer', 'q_bool' => '1', 'q_mc' => ['s2']],
            'confirm' => '1',
        ])->assertRedirect($url)->assertSessionHasErrors(['email', 'resume']);

    $response = $this->get($url)->assertOk()
        ->assertSee('value="Retained Candidate"', false)
        ->assertSee('Retained cover letter')
        ->assertSee('Remember this answer')
        ->assertSee('choose your files again');
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent(), LIBXML_NONET);
    $xpath = new DOMXPath($document);
    expect($xpath->query('//input[@name="answers[q_mc][]" and @value="s2" and @checked]')->length)->toBe(1);
});

it('honors a lower schema upload limit in both the wizard and server validation', function () {
    Bus::fake();
    fakeApplicationForm([
        'form_fields' => [
            ['key' => 'resume', 'type' => 'file', 'required' => true, 'max_file_size' => 2 * 1024 * 1024],
        ],
        'questions' => [],
    ]);
    nativePosting();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('workable-jobs.apply', 'SENIO001'))->assertOk();
    expect(collect($response->viewData('form')['fields'])->firstWhere('key', 'resume')['max_file_size'])->toBe(2 * 1024 * 1024);

    $this->post(route('workable-jobs.apply.store', 'SENIO001'), [
        'firstname' => 'Cand', 'lastname' => 'Idate', 'email' => 'cand@example.com', 'confirm' => '1',
        'resume' => UploadedFile::fake()->create('too-large.pdf', 2049, 'application/pdf'),
    ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('resume');

    expect(JobApplication::count())->toBe(0);
    Bus::assertNotDispatched(SubmitJobApplicationJob::class);
});
