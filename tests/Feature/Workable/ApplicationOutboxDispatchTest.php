<?php

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Jobs\Workable\SubmitJobApplicationJob;
use App\Actions\Workable\DispatchQueuedApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

function queuedWorkableApplication(array $attributes = []): JobApplication
{
    $company = Company::factory()->create([
        'apply_mode' => 'native',
        'credentials_status' => 'valid',
    ]);
    $posting = JobPosting::factory()->for($company)->create([
        'shortcode' => (string) Str::upper(Str::random(8)),
        'apply_supported' => true,
    ]);

    return JobApplication::create([
        'job_posting_id' => $posting->id,
        'idempotency_key' => (string) Str::uuid(),
        'email' => Str::uuid().'@example.com',
        'first_name' => 'Test',
        'last_name' => 'Applicant',
        'status' => 'queued',
        ...$attributes,
    ]);
}

beforeEach(function () {
    config(['queue.default' => 'database']);
});

it('records a durable dispatch attempt and prevents immediate duplicate dispatches', function () {
    Bus::fake();
    $application = queuedWorkableApplication();
    $dispatcher = app(DispatchQueuedApplication::class);

    expect($dispatcher->dispatch($application->id))->toBeTrue()
        ->and($dispatcher->dispatch($application->id))->toBeFalse();

    $fresh = $application->fresh();
    expect($fresh->dispatch_token)->toBeString()->not->toBeEmpty()
        ->and($fresh->dispatch_attempted_at)->not->toBeNull()
        ->and($fresh->dispatch_attempts)->toBe(1);

    Bus::assertDispatchedTimes(SubmitJobApplicationJob::class, 1);
    Bus::assertDispatched(SubmitJobApplicationJob::class, fn (SubmitJobApplicationJob $job): bool =>
        $job->application->is($fresh) && $job->dispatchToken === $fresh->dispatch_token
    );
});

it('redispatches stale queued submissions and parks expired workers for review', function () {
    Bus::fake();
    $application = queuedWorkableApplication([
        'dispatch_token' => (string) Str::uuid(),
        'dispatch_attempted_at' => now()->subMinutes(20),
    ]);
    $interrupted = queuedWorkableApplication([
        'status' => 'submitting',
        'dispatch_token' => (string) Str::uuid(),
        'processing_lease_until' => now()->subMinute(),
    ]);

    $this->artisan('workable:dispatch-applications')
        ->assertSuccessful();

    $application->refresh();
    expect($application->dispatch_attempts)->toBe(1)
        ->and($application->dispatch_token)->not->toBeNull()
        ->and($application->dispatch_attempted_at)->toBeGreaterThan(now()->subMinute());
    expect($interrupted->fresh()->status)->toBe('needs_review')
        ->and($interrupted->fresh()->error_code)->toBe('worker_interrupted');
    Bus::assertDispatchedTimes(SubmitJobApplicationJob::class, 1);
});

it('waits for a transient Workable retry time before dispatching again', function () {
    Bus::fake();
    $application = queuedWorkableApplication([
        'dispatch_attempted_at' => now()->subMinutes(20),
        'next_attempt_at' => now()->addMinutes(3),
    ]);

    $this->artisan('workable:dispatch-applications')->assertSuccessful();

    expect($application->fresh()->dispatch_attempts)->toBe(0);
    Bus::assertNothingDispatched();

    $this->travel(4)->minutes();
    $this->artisan('workable:dispatch-applications')->assertSuccessful();

    expect($application->fresh()->dispatch_attempts)->toBe(1);
    Bus::assertDispatchedTimes(SubmitJobApplicationJob::class, 1);
});
