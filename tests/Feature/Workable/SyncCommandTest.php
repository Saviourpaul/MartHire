<?php

use App\Actions\Workable\SyncCompanyJobs;
use App\Jobs\Workable\SyncCompanyJobsJob;
use App\Models\Company;
use App\Models\SyncRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function syncMakeCompany(string $subdomain): Company
{
    return Company::create([
        'subdomain' => $subdomain,
        'name' => ucfirst($subdomain),
        'slug' => $subdomain,
        'apply_mode' => 'redirect',
        'credentials_status' => 'unverified',
    ]);
}

it('dispatches one job per company', function () {
    Queue::fake();
    syncMakeCompany('alpha');
    syncMakeCompany('beta');

    $this->artisan('workable:sync')->assertSuccessful();

    Queue::assertPushed(SyncCompanyJobsJob::class, 2);
});

it('can target one company by subdomain or id', function () {
    Queue::fake();
    $alpha = syncMakeCompany('alpha');
    syncMakeCompany('beta');

    $this->artisan('workable:sync', ['--company' => 'alpha'])->assertSuccessful();
    Queue::assertPushed(SyncCompanyJobsJob::class, 1);
    Queue::assertPushed(SyncCompanyJobsJob::class, fn ($job) => $job->company->is($alpha));

    Queue::fake();
    $this->artisan('workable:sync', ['--company' => (string) $alpha->id])->assertSuccessful();
    Queue::assertPushed(SyncCompanyJobsJob::class, 1);
    Queue::assertPushed(SyncCompanyJobsJob::class, fn ($job) => $job->company->is($alpha));
});

it('fails when the targeted company does not exist', function () {
    Queue::fake();

    $this->artisan('workable:sync', ['--company' => 'nope'])->assertFailed();

    Queue::assertNothingPushed();
});

it('runs the sync action for its company', function () {
    $company = syncMakeCompany('alpha');
    $action = Mockery::mock(SyncCompanyJobs::class);
    $action->shouldReceive('__invoke')->once()
        ->with(Mockery::on(fn ($c) => $c->is($company)))
        ->andReturn(new SyncRun);

    (new SyncCompanyJobsJob($company))->handle($action);
});

it('does not hide a non-Workable failure from the queue', function () {
    $company = syncMakeCompany('alpha');
    $action = Mockery::mock(SyncCompanyJobs::class);
    $action->shouldReceive('__invoke')->once()->andThrow(new RuntimeException('db down'));

    expect(fn () => (new SyncCompanyJobsJob($company))->handle($action))
        ->toThrow(RuntimeException::class, 'db down');
});
