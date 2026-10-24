<?php

use App\Models\Company;
use App\Models\JobPosting;
use App\Models\SyncRun;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function jpCompany(string $slug = 'acme'): Company
{
    return Company::create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'subdomain' => $slug,
        'api_token' => 'secret-xyz',
    ]);
}

function jpAttrs(array $o = []): array
{
    return array_merge([
        'shortcode' => 'ABC123',
        'external_id' => 'ext-1',
        'title' => 'Engineer',
        'state' => 'published',
    ], $o);
}

it('belongs to a company', function () {
    $company = jpCompany();
    $posting = $company->jobPostings()->create(jpAttrs());

    expect($posting->company->is($company))->toBeTrue();
    expect($company->jobPostings)->toHaveCount(1);
});

it('enforces unique shortcode per company', function () {
    $a = jpCompany('a');
    $b = jpCompany('b');
    $a->jobPostings()->create(jpAttrs());

    expect(fn () => $a->jobPostings()->create(jpAttrs(['external_id' => 'ext-2'])))
        ->toThrow(QueryException::class);

    $b->jobPostings()->create(jpAttrs());
    expect(JobPosting::count())->toBe(2);
});

it('casts json and datetime columns', function () {
    $company = jpCompany();
    $posting = $company->jobPostings()->create(jpAttrs([
        'location' => ['city' => 'Lagos', 'country' => 'NG'],
        'keywords' => ['php', 'laravel'],
        'external_updated_at' => '2026-10-01 12:00:00',
        'apply_supported' => true,
    ]));

    $fresh = JobPosting::find($posting->id);
    expect($fresh->location)->toBe(['city' => 'Lagos', 'country' => 'NG']);
    expect($fresh->keywords)->toBe(['php', 'laravel']);
    expect($fresh->external_updated_at)->toBeInstanceOf(DateTimeInterface::class);
    expect($fresh->apply_supported)->toBeTrue();
    expect($fresh->status)->toBe('open');
});

it('sync run start and finish set counts and status', function () {
    $company = jpCompany();
    $run = SyncRun::start($company);
    $run->finish(['fetched' => 3, 'created' => 1, 'closed' => 2, 'status' => 'ok']);

    $fresh = SyncRun::find($run->id);
    expect($fresh->started_at)->not->toBeNull();
    expect($fresh->finished_at)->not->toBeNull();
    expect([$fresh->fetched, $fresh->created, $fresh->updated, $fresh->closed])->toBe([3, 1, 0, 2]);
    expect($fresh->status)->toBe('ok');
    expect($fresh->company->is($company))->toBeTrue();
});
