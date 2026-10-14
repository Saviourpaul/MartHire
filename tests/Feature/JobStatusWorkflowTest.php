<?php

use App\Enums\JobStatus;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use App\Services\AdminReportService;
use App\Services\ApplicationFormService;
use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => app(OrganizationContext::class)->set(null, true));

function jobWorkflowPayload(Job $job): array
{
    return [...Arr::only($job->getAttributes(), [
        'title', 'description', 'company', 'category', 'logo',
    ]), 'location' => $job->location ?: 'Lagos', 'employment_type' => $job->employment_type ?: 'full_time',
        'start_date' => $job->start_date->toDateString(), 'due_date' => $job->due_date->toDateString()];
}

it('uses exactly three statuses and defaults new jobs to open in the model and database', function () {
    expect(JobStatus::values())->toBe(['open', 'suspended', 'closed'])
        ->and((new Job)->status)->toBe(JobStatus::Open);
    $job = Job::factory()->create();
    $record = Arr::except($job->getAttributes(), ['id', 'status']);
    $record['slug'] = 'database-default-job';
    $id = DB::table('job_posts')->insertGetId($record);
    expect(DB::table('job_posts')->where('id', $id)->value('status'))->toBe('open');
});

it('reopens suspended jobs when the employer saves corrected content without a separate resubmission flag', function () {
    $admin = User::factory()->admin()->create();
    $job = Job::factory()->suspended()->create([
        'location' => 'Lagos', 'employment_type' => 'full_time', 'suspended_by' => $admin->id,
    ]);
    $application = ApplicationForm::factory()->for($job)->create();
    $reason = $job->suspension_reason;
    $timestamp = $job->suspended_at;
    $this->actingAs($job->employer)->get(route('jobs.show', $job))->assertOk()
        ->assertSee('Save corrections and reopen')->assertSee($reason)->assertDontSee('name="resubmit"', false);
    $this->putJson(route('jobs.update', $job), [...jobWorkflowPayload($job), 'title' => 'Corrected role', 'description' => '<p>Corrected requirements.</p>'])
        ->assertOk()->assertJsonPath('message', 'Corrections saved. Job reopened for applications.');
    expect($job->refresh()->status)->toBe(JobStatus::Open)
        ->and($job->isSuspended())->toBeFalse()
        ->and($job->isAcceptingApplications())->toBeTrue()
        ->and($job->suspension_reason)->toBe($reason)
        ->and($job->suspended_at->eq($timestamp))->toBeTrue()
        ->and($job->suspended_by)->toBe($admin->id)
        ->and(Job::query()->acceptingApplications()->whereKey($job)->exists())->toBeTrue();
    $this->assertModelExists($application);
    $this->get(route('Browse-jobs'))->assertOk()->assertSee('Corrected role');
    $this->actingAs($admin)->get(route('admin.jobs.show', $job))->assertOk()->assertSee('Previous suspension');
    $this->patchJson(route('admin.jobs.suspend', $job), ['suspension_reason' => 'A new policy violation.'])->assertOk();
    expect($job->refresh()->status)->toBe(JobStatus::Suspended)
        ->and($job->suspension_reason)->toBe('A new policy violation.');
});

it('preserves open and closed statuses during employer edits regardless of obsolete status controls', function (JobStatus $status) {
    $job = Job::factory()->create(['status' => $status]);
    $this->actingAs($job->employer)->putJson(route('jobs.update', $job), [
        ...jobWorkflowPayload($job), 'title' => 'Changed role', 'resubmit' => 1,
    ])->assertOk();
    $this->putJson(route('jobs.update', $job), [...jobWorkflowPayload($job), 'title' => 'Updated role', 'status' => 'open', 'resubmit' => ['invalid']])
        ->assertOk();
    expect($job->fresh()->status)->toBe($status)->and($job->fresh()->title)->toBe('Updated role');
})->with([JobStatus::Open, JobStatus::Closed]);

it('requires a valid application deadline to reopen a suspended job', function () {
    $job = Job::factory()->suspended()->create(['start_date' => today()->subMonth(), 'due_date' => today()->subDay()]);
    $this->actingAs($job->employer)->putJson(route('jobs.update', $job), [
        ...jobWorkflowPayload($job), 'title' => 'Corrected role',
    ])->assertUnprocessable()->assertJsonValidationErrors('due_date');
    expect($job->fresh()->status)->toBe(JobStatus::Suspended);
});

it('keeps suspended jobs unchanged when corrections fail validation', function (string $field, mixed $value) {
    $job = Job::factory()->suspended()->create();
    $before = $job->refresh()->getAttributes();
    $this->actingAs($job->employer)->putJson(route('jobs.update', $job), [
        ...jobWorkflowPayload($job), 'title' => 'Corrected role', $field => $value,
    ])->assertUnprocessable()->assertJsonValidationErrors($field);
    expect($job->fresh()->getAttributes())->toBe($before);
})->with([
    'empty description' => ['description', '<p><br></p>'],
    'invalid title' => ['title', ['invalid']],
    'invalid employment type' => ['employment_type', 'invalid'],
]);

it('lets employers resave previously corrected content to reopen a job through the normal form', function () {
    $job = Job::factory()->suspended()->create([
        'description' => '<p>Previously saved corrections.</p>', 'location' => 'Lagos', 'employment_type' => 'full_time',
    ]);
    $this->actingAs($job->employer)->get(route('jobs'))->assertOk()
        ->assertSee('Save corrections and reopen')->assertDontSee('name="resubmit"', false);
    $this->put(route('jobs.update', $job), [...jobWorkflowPayload($job), 'resubmit' => 0])
        ->assertRedirect(route('jobs'))->assertSessionHas('success', 'Corrections saved. Job reopened for applications.');
    expect($job->fresh()->status)->toBe(JobStatus::Open)
        ->and($job->fresh()->description)->toBe($job->description);
});

it('safely replaces the logo when an employer corrects and reopens a job', function () {
    Storage::fake('public');
    Storage::disk('public')->put('job-logos/original.png', 'original');
    $job = Job::factory()->suspended()->create(['logo' => 'job-logos/original.png']);
    $this->actingAs($job->employer)->putJson(route('jobs.update', $job), [
        ...jobWorkflowPayload($job),
        'logo' => UploadedFile::fake()->createWithContent('corrected.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lZ0AAAAASUVORK5CYII=')),
    ])->assertOk()->assertSessionHas('success');
    expect($job->refresh()->status)->toBe(JobStatus::Open);
    Storage::disk('public')->assertMissing('job-logos/original.png');
    Storage::disk('public')->assertExists($job->logo);
    expect(Storage::disk('public')->allFiles())->toHaveCount(1);
});

it('allows owners to close their jobs without deleting applications and preserves closure when editing', function (JobStatus $status) {
    $job = Job::factory()->create(['status' => $status]);
    $application = ApplicationForm::factory()->for($job)->create();
    $this->actingAs($job->employer)->patchJson(route('jobs.close', $job), [])
        ->assertUnprocessable()->assertJsonValidationErrors('confirm_closure');
    $this->patchJson(route('jobs.close', $job), ['confirm_closure' => 1])->assertOk();
    expect($job->refresh()->status)->toBe(JobStatus::Closed);
    $this->putJson(route('jobs.update', $job), [...jobWorkflowPayload($job), 'title' => 'Updated closed role', 'status' => 'open'])
        ->assertOk();
    expect($job->refresh()->status)->toBe(JobStatus::Closed)->and($job->isAcceptingApplications())->toBeFalse();
    $this->assertModelExists($application);
    $this->patchJson(route('jobs.close', $job), ['confirm_closure' => 1])->assertUnprocessable();
})->with([JobStatus::Open, JobStatus::Suspended]);

it('prevents other employers candidates and admins from closing or reopening employer jobs', function (string $role) {
    $job = Job::factory()->suspended()->create();
    $user = User::factory()->{$role}()->create();
    $expectedStatus = $role === 'employer' ? 404 : 403;
    $this->actingAs($user)->patchJson(route('jobs.close', $job), ['confirm_closure' => 1])->assertStatus($expectedStatus);
    $this->putJson(route('jobs.update', $job), [...jobWorkflowPayload($job), 'title' => 'Unauthorized change'])->assertStatus($expectedStatus);
    expect(Job::withoutGlobalScopes()->findOrFail($job->id)->status)->toBe(JobStatus::Suspended);
})->with(['employer', 'applicant', 'admin']);

it('limits public discovery and candidate application access to open jobs', function (JobStatus $status) {
    $job = Job::factory()->create(['title' => 'Workflow visibility fixture', 'status' => $status, 'due_date' => today()->addDays(30)]);
    expect($job->isAcceptingApplications())->toBe($status === JobStatus::Open)
        ->and(Job::query()->acceptingApplications()->whereKey($job)->exists())->toBe($status === JobStatus::Open);
    foreach (['Browse-jobs', 'client.jobs-listings'] as $route) {
        $response = $this->get(route($route))->assertOk();
        $status === JobStatus::Open ? $response->assertSee($job->title) : $response->assertDontSee($job->title);
    }
    $this->actingAs(User::factory()->completeApplicantProfile()->create());
    if ($status === JobStatus::Open) {
        $this->get(route('applications.create', $job))->assertOk();
    } elseif ($status === JobStatus::Closed) {
        $this->get(route('applications.create', $job))->assertGone()->assertSee('Applications closed');
        $this->postJson(route('applications.store', $job), [])->assertGone();
        $this->get(route('job-details', $job))->assertOk()->assertSee('Closed')->assertSee('not accepting applications')->assertDontSee('Apply Now');
    } else {
        $this->get(route('applications.create', $job))->assertNotFound();
        $this->postJson(route('applications.store', $job), [])->assertForbidden();
        $this->get(route('job-details', $job))->assertNotFound();
    }
})->with(JobStatus::cases());

it('rejects stale application submissions after an employer closes the job', function () {
    $job = Job::factory()->open()->create();
    $candidate = User::factory()->applicant()->create();
    Job::query()->whereKey($job)->update(['status' => JobStatus::Closed]);
    expect(fn () => app(ApplicationFormService::class)->submit($job, $candidate, []))
        ->toThrow(ValidationException::class, 'This job is not accepting applications.');
    $this->assertDatabaseMissing('application_forms', ['job_id' => $job->id]);
});

it('shows current status counts in both dashboards and scopes employer status filters to the owner', function () {
    $employer = User::factory()->employer()->create();
    $jobs = [];
    foreach (JobStatus::cases() as $status) {
        $jobs[$status->value] = Job::factory()->for($employer, 'employer')->create(['status' => $status, 'title' => ucfirst($status->value).' owned fixture']);
    }
    $otherJob = Job::factory()->open()->create(['title' => 'Other employer fixture']);
    $this->actingAs($employer)->get(route('dashboard'))->assertOk()
        ->assertSee('Total Jobs Posted')
        ->assertViewHas('jobStatusCounts', fn ($counts): bool => $counts->map(fn ($count): int => (int) $count)->all() === ['closed' => 1, 'open' => 1, 'suspended' => 1]);
    foreach ($jobs as $status => $job) {
        $response = $this->get(route('jobs', ['status' => $status]))->assertOk()->assertSee($job->title)->assertDontSee($otherJob->title);
        foreach ($jobs as $otherStatus => $otherOwnedJob) {
            if ($otherStatus !== $status) {
                $response->assertDontSee($otherOwnedJob->title);
            }
        }
    }
    $this->get(route('jobs', ['status' => ['invalid']]))->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))->assertOk()
        ->assertSee('Total Jobs Posted')
        ->assertViewHas('jobStatusCounts', fn ($counts): bool => (int) $counts['open'] === 2 && (int) $counts['suspended'] === 1 && (int) $counts['closed'] === 1);
    $report = app(AdminReportService::class)->getReport(Request::create('/Reports'));
    expect($report['charts']['jobModeration']['labels'])->toBe(['Open', 'Suspended', 'Closed'])
        ->and($report['charts']['jobModeration']['series'])->toBe([2, 1, 1])
        ->and($report['metrics']['active_job_postings'])->toBe(2)
        ->and($report['metrics']['suspended_jobs'])->toBe(1);
});

it('keeps previous applications accessible on closed job details without offering a new application', function () {
    $job = Job::factory()->closed()->create();
    $application = ApplicationForm::factory()->for($job)->create();
    $this->actingAs($application->applicant)->get(route('job-details', $job))->assertOk()
        ->assertSee('not accepting applications')->assertSee(route('client.applications.show', $application), false)
        ->assertDontSee('Apply Now');
});

it('automatically closes expired open jobs without changing suspended jobs or today deadlines', function () {
    $expired = Job::factory()->open()->create(['due_date' => today()->subDay()]);
    $todayJob = Job::factory()->open()->create(['due_date' => today()]);
    $suspended = Job::factory()->suspended()->create(['due_date' => today()->subDay()]);
    $this->artisan('jobs:close-expired')->expectsOutput('Closed 1 expired jobs.')->assertSuccessful();
    expect($expired->fresh()->status)->toBe(JobStatus::Closed)
        ->and($todayJob->fresh()->status)->toBe(JobStatus::Open)
        ->and($suspended->fresh()->status)->toBe(JobStatus::Suspended);
    $this->artisan('jobs:close-expired')->expectsOutput('Closed 0 expired jobs.')->assertSuccessful();
});

it('migrates legacy statuses and existing suspension metadata without losing job content', function () {
    $admin = User::factory()->admin()->create();
    $legacy = [];
    foreach (['approved', 'pending', 'rejected', 'inactive'] as $status) {
        $job = Job::factory()->create(['due_date' => today()->addDays(30)]);
        DB::table('job_posts')->where('id', $job->id)->update(['status' => $status]);
        $legacy[$status] = $job;
    }
    $restricted = Job::factory()->create(['suspended_at' => now(), 'suspension_reason' => 'Preserve this reason.', 'suspended_by' => $admin->id]);
    $reopened = Job::factory()->open()->create(['suspended_at' => now(), 'suspension_reason' => 'A resolved issue.']);
    DB::table('job_posts')->where('id', $restricted->id)->update(['status' => 'approved']);
    $expired = Job::factory()->create(['due_date' => today()->subDay()]);
    DB::table('job_posts')->where('id', $expired->id)->update(['status' => 'approved']);
    $migration = require database_path('migrations/2026_10_12_035123_reconcile_existing_job_statuses.php');
    $migration->up();
    foreach (['approved' => JobStatus::Open, 'pending' => JobStatus::Open, 'rejected' => JobStatus::Suspended, 'inactive' => JobStatus::Closed] as $oldStatus => $newStatus) {
        expect($legacy[$oldStatus]->fresh()->status)->toBe($newStatus)
            ->and($legacy[$oldStatus]->fresh()->description)->toBe($legacy[$oldStatus]->description);
    }
    expect($restricted->fresh()->status)->toBe(JobStatus::Suspended)
        ->and($restricted->fresh()->suspension_reason)->toBe('Preserve this reason.')
        ->and($restricted->fresh()->suspended_by)->toBe($admin->id)
        ->and($expired->fresh()->status)->toBe(JobStatus::Closed)
        ->and($legacy['rejected']->fresh()->suspension_reason)->not->toBeNull();
    $migration->up();
    expect($reopened->fresh()->status)->toBe(JobStatus::Open)
        ->and($reopened->fresh()->suspension_reason)->toBe('A resolved issue.')
        ->and($restricted->fresh()->suspension_reason)->toBe('Preserve this reason.');
    $migration->down();
    expect(DB::table('job_posts')->where('id', $legacy['approved']->id)->value('status'))->toBe('approved')
        ->and(DB::table('job_posts')->where('id', $restricted->id)->value('status'))->toBe('rejected');
});
