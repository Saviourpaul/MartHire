<?php

use App\Jobs\Workable\SubmitJobApplicationJob;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * @return array{0: Company, 1: JobPosting, 2: JobApplication}
 */
function outboxFixture(string $candidatesResponseFixture, int $candidatesStatus): array
{
    $form = json_decode(file_get_contents(base_path('tests/Fixtures/workable/application_form.json')), true);
    $candidateBody = json_decode(file_get_contents(base_path("tests/Fixtures/workable/$candidatesResponseFixture")), true);

    Http::fake([
        '*/spi/v3/jobs/SENIO001/application_form*' => Http::response($form, 200),
        '*/spi/v3/jobs/SENIO001/candidates*' => Http::response($candidateBody, $candidatesStatus),
    ]);

    Storage::fake('local');
    Storage::disk('local')->put('applications/1/cv.pdf', 'PDF-BYTES');

    $company = Company::factory()->create([
        'subdomain' => 'pilotco', 'api_token' => 'tok_123',
        'apply_mode' => 'native', 'credentials_status' => 'valid',
    ]);
    $posting = JobPosting::factory()->for($company)->create(['shortcode' => 'SENIO001', 'apply_supported' => true]);

    $application = JobApplication::create([
        'job_posting_id' => $posting->id,
        'idempotency_key' => (string) Str::uuid(),
        'email' => 'cand@example.com', 'first_name' => 'Cand', 'last_name' => 'Idate',
        'status' => 'queued',
        'payload' => [
            'data' => ['first_name' => 'Cand', 'last_name' => 'Idate', 'email' => 'cand@example.com', 'question_q_free' => 'hi', 'question_q_bool' => true],
            'files' => ['resume' => ['path' => 'applications/1/cv.pdf', 'name' => 'cv.pdf']],
        ],
    ]);

    return [$company, $posting, $application];
}

function runOutbox(JobApplication $application): void
{
    app()->call([new SubmitJobApplicationJob($application), 'handle']);
}

it('submits successfully, records the candidate, and purges the payload and file', function () {
    [, , $application] = outboxFixture('candidate_created.json', 201);

    runOutbox($application);

    $fresh = $application->fresh();
    expect($fresh->status)->toBe('submitted');
    expect($fresh->submitted_at)->not->toBeNull();
    expect($fresh->workable_candidate_id)->toBe('cand_123');
    expect($fresh->payload)->toBeNull();                              // sensitive data purged
    expect(Storage::disk('local')->exists('applications/1/cv.pdf'))->toBeFalse();
    Http::assertSent(fn ($r) => str_contains($r->url(), '/candidates') && $r->method() === 'POST');
});

it('flips the company to redirect and fails_credentials on a rejected key', function () {
    [$company, , $application] = outboxFixture('candidate_422.json', 401);

    runOutbox($application);

    expect($application->fresh()->status)->toBe('failed_credentials');
    expect($company->fresh()->credentials_status)->toBe('invalid');
    expect($company->fresh()->apply_mode)->toBe('redirect');
    expect($application->fresh()->payload)->not->toBeNull();          // kept for ops
});

it('marks the posting closed and purges on a 404', function () {
    [, $posting, $application] = outboxFixture('candidate_422.json', 404);

    runOutbox($application);

    expect($application->fresh()->status)->toBe('failed_job_closed');
    expect($posting->fresh()->status)->toBe('closed');
    expect($application->fresh()->payload)->toBeNull();
});

it('records a permanent failure with PII-free detail on a 422', function () {
    [, , $application] = outboxFixture('candidate_422.json', 422);

    runOutbox($application);

    $fresh = $application->fresh();
    expect($fresh->status)->toBe('failed_permanent');
    expect($fresh->error_code)->toBe('duplicate_or_invalid_email');
    expect($fresh->response_excerpt)->toBe('email');                 // keys only, no values
});

it('parks an ambiguous outcome for review and never re-posts', function () {
    [, , $application] = outboxFixture('candidate_422.json', 500);

    runOutbox($application);

    expect($application->fresh()->status)->toBe('needs_review');
    Http::assertSentCount(2); // one application_form GET + exactly one candidates POST
});

it('is idempotent — a submitted application is not posted again', function () {
    [, , $application] = outboxFixture('candidate_created.json', 201);
    $application->update(['status' => 'submitted']);

    runOutbox($application);

    Http::assertNothingSent();
});
