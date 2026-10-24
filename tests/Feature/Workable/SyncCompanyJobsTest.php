<?php

use App\Actions\Workable\SyncCompanyJobs;
use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['workable.fake' => false]);
    $this->company = Company::create([
        'name' => 'Pilot', 'slug' => 'pilot', 'subdomain' => 'pilotco', 'api_token' => 'tok_123',
    ]);
});

function scFixture(): array
{
    return json_decode(file_get_contents(base_path('tests/Fixtures/workable/jobs_published.json')), true);
}

function scFakePublished(): void
{
    Http::fake(['pilotco.workable.com/spi/v3/jobs*' => Http::response(scFixture())]);
}

function scSeed(Company $company, string $shortcode, array $o = []): JobPosting
{
    return $company->jobPostings()->create(array_merge([
        'shortcode' => $shortcode,
        'external_id' => 'ext-'.$shortcode,
        'title' => 'Seeded',
        'state' => 'published',
        'status' => 'open',
        'last_seen_at' => now()->subDay(),
    ], $o));
}

function scSnapshot(Company $company): array
{
    return $company->jobPostings()->orderBy('shortcode')->get()
        ->map->only(['shortcode', 'content_hash', 'content_html', 'status'])->all();
}

it('ingests published jobs and sets counts', function () {
    scFakePublished();

    $run = app(SyncCompanyJobs::class)($this->company);

    expect($this->company->jobPostings()->where('status', 'open')->count())->toBe(2);
    expect($run->status)->toBe('ok')
        ->and($run->fetched)->toBe(2)
        ->and($run->created)->toBe(2)
        ->and($run->updated)->toBe(0)
        ->and($run->closed)->toBe(0);
    expect($this->company->fresh()->last_synced_at)->not->toBeNull();
});

it('is idempotent on a second run', function () {
    scFakePublished();
    app(SyncCompanyJobs::class)($this->company);
    $before = scSnapshot($this->company);

    $run = app(SyncCompanyJobs::class)($this->company);

    expect($run->status)->toBe('ok')
        ->and($run->created)->toBe(0)
        ->and($run->updated)->toBe(0)
        ->and($run->closed)->toBe(0);
    expect(scSnapshot($this->company))->toBe($before);
    expect($this->company->jobPostings()->count())->toBe(2);
});

it('sweeps a disappeared job closed', function () {
    scFakePublished();
    $gone = scSeed($this->company, 'GONE999');

    $run = app(SyncCompanyJobs::class)($this->company);

    $gone->refresh();
    expect($gone->status)->toBe('closed')->and($gone->closed_at)->not->toBeNull();
    expect($run->closed)->toBe(1);
});

it('reopens a reappearing job', function () {
    scFakePublished();
    $job = scSeed($this->company, 'SENIO001', ['status' => 'closed', 'closed_at' => now()->subDay()]);

    $run = app(SyncCompanyJobs::class)($this->company);

    $job->refresh();
    expect($job->status)->toBe('open')->and($job->closed_at)->toBeNull();
    expect($run->created)->toBe(1)->and($run->updated)->toBe(1);
});

it('treats an empty response with open postings as suspicious and does not sweep', function () {
    Http::fake(['pilotco.workable.com/spi/v3/jobs*' => Http::response(['jobs' => [], 'paging' => ['next' => null]])]);
    foreach (range(1, 5) as $i) {
        scSeed($this->company, "SEED00{$i}");
    }

    $run = app(SyncCompanyJobs::class)($this->company);

    expect($run->status)->toBe('suspicious');
    expect($this->company->jobPostings()->where('status', 'open')->count())->toBe(5);
});

it('does not sweep when the fetch fails', function () {
    Http::fake(['pilotco.workable.com/spi/v3/jobs*' => Http::response('boom', 500)]);
    $posting = scSeed($this->company, 'KEEP001');

    $run = app(SyncCompanyJobs::class)($this->company);

    expect($run->status)->toBe('failed');
    expect($run->error)->toContain('TransientFailure');
    expect($posting->fresh()->status)->toBe('open');
});

it('marks the run failed and rethrows on an unexpected crash', function () {
    $client = Mockery::mock(App\Integrations\Workable\WorkableClient::class);
    $client->shouldReceive('listPublishedJobs')->once()->andThrow(new RuntimeException('boom'));

    expect(fn () => (new SyncCompanyJobs($client))($this->company))
        ->toThrow(RuntimeException::class, 'boom');

    $run = App\Models\SyncRun::where('company_id', $this->company->id)->latest('id')->first();
    expect($run->status)->toBe('failed')
        ->and($run->finished_at)->not->toBeNull()
        ->and($run->error)->toBe(RuntimeException::class);
});
