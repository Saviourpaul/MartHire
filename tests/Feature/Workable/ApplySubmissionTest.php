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

function fakeApplicationForm(): void
{
    Http::fake([
        '*/spi/v3/jobs/SENIO001/application_form*' => Http::response(
            json_decode(file_get_contents(base_path('tests/Fixtures/workable/application_form.json')), true),
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

    $response = $this->actingAs(User::factory()->create())->get('/jobs/SENIO001/apply');

    $response->assertOk();
    $response->assertViewHas('form');
    $form = $response->viewData('form');
    expect(collect($form['questions'])->pluck('id'))->toContain('q_free', 'q_bool', 'q_mc');
    expect(collect($form['fields'])->pluck('key'))->toContain('firstname', 'lastname', 'resume');
});

it('queues a native application from the blade field names, stores the resume privately, dispatches the job', function () {
    Bus::fake();
    Storage::fake('local');
    fakeApplicationForm();
    $posting = nativePosting();

    $response = $this->actingAs(User::factory()->create())
        ->post('/jobs/SENIO001/apply', bladePayload());

    $response->assertRedirect(route('jobs.show', 'SENIO001'));

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
        ->from('/jobs/SENIO001/apply')->post('/jobs/SENIO001/apply', $payload);

    $response->assertSessionHasErrors('confirm');
    expect(JobApplication::count())->toBe(0);
    Bus::assertNotDispatched(SubmitJobApplicationJob::class);
});

it('rejects an invalid submission without creating an application', function () {
    Bus::fake();
    fakeApplicationForm();
    nativePosting();

    $response = $this->actingAs(User::factory()->create())
        ->from('/jobs/SENIO001/apply')
        ->post('/jobs/SENIO001/apply', ['firstname' => 'Cand']); // missing almost everything

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

    $response = $this->actingAs(User::factory()->create())->post('/jobs/REDIR001/apply', [
        'firstname' => 'X', 'lastname' => 'Y', 'email' => 'x@example.com', 'confirm' => '1',
    ]);

    $response->assertRedirect('https://pilotco.workable.com/j/REDIR001');
    expect(JobApplication::count())->toBe(0);
    Bus::assertNotDispatched(SubmitJobApplicationJob::class);
});

it('requires authentication to submit', function () {
    nativePosting();

    $this->post('/jobs/SENIO001/apply', ['email' => 'x@example.com'])->assertRedirect('/login');
    expect(JobApplication::count())->toBe(0);
});
