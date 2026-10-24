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

it('queues a native application, stores the resume privately, and dispatches the submit job', function () {
    Bus::fake();
    Storage::fake('local');
    fakeApplicationForm();

    $company = Company::factory()->create([
        'subdomain' => 'pilotco', 'api_token' => 'tok_123',
        'apply_mode' => 'native', 'credentials_status' => 'valid',
    ]);
    $posting = JobPosting::factory()->for($company)->create([
        'shortcode' => 'SENIO001', 'status' => 'open', 'apply_supported' => true,
    ]);

    $response = $this->actingAs(User::factory()->create())->post('/jobs/SENIO001/apply', [
        'first_name' => 'Cand', 'last_name' => 'Idate', 'email' => 'cand@example.com',
        'resume' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'),
        'question_q_free' => 'because', 'question_q_bool' => '1',
        'question_q_drop' => 'c1', 'question_q_mc' => ['s1'],
    ]);

    $response->assertRedirect(route('jobs.show', 'SENIO001'));

    $application = JobApplication::first();
    expect($application)->not->toBeNull();
    expect($application->status)->toBe('queued');
    expect($application->email)->toBe('cand@example.com');
    expect($application->payload['files']['resume']['path'])->toContain("applications/{$posting->id}/");
    expect($application->payload['data'])->not->toHaveKey('resume'); // file never in the data payload
    Storage::disk('local')->assertExists($application->payload['files']['resume']['path']);

    Bus::assertDispatched(SubmitJobApplicationJob::class, fn ($job) => $job->application->is($application));
});

it('rejects an invalid submission without creating an application', function () {
    Bus::fake();
    fakeApplicationForm();

    $company = Company::factory()->create(['subdomain' => 'pilotco', 'api_token' => 'tok_123', 'apply_mode' => 'native']);
    JobPosting::factory()->for($company)->create(['shortcode' => 'SENIO001', 'status' => 'open', 'apply_supported' => true]);

    $response = $this->actingAs(User::factory()->create())
        ->from('/jobs/SENIO001/apply')
        ->post('/jobs/SENIO001/apply', ['first_name' => 'Cand']); // missing email, last_name, resume, required questions

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
        'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@example.com',
    ]);

    $response->assertRedirect('https://pilotco.workable.com/j/REDIR001');
    expect(JobApplication::count())->toBe(0);
    Bus::assertNotDispatched(SubmitJobApplicationJob::class);
});

it('requires authentication to submit', function () {
    $company = Company::factory()->create(['apply_mode' => 'native']);
    JobPosting::factory()->for($company)->create(['shortcode' => 'SENIO001', 'status' => 'open', 'apply_supported' => true]);

    $this->post('/jobs/SENIO001/apply', ['email' => 'x@example.com'])->assertRedirect('/login');
    expect(JobApplication::count())->toBe(0);
});
