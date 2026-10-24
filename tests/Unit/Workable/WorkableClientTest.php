<?php

use App\Integrations\Workable\Exceptions\AmbiguousOutcome;
use App\Integrations\Workable\Exceptions\CredentialsRejected;
use App\Integrations\Workable\Exceptions\JobNotFound;
use App\Integrations\Workable\Exceptions\PayloadRejected;
use App\Integrations\Workable\Exceptions\RateLimited;
use App\Integrations\Workable\Exceptions\TransientFailure;
use App\Integrations\Workable\WorkableClient;
use App\Models\Company;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

function wkFixture(string $name): array
{
    return json_decode(file_get_contents(base_path("tests/Fixtures/workable/{$name}")), true);
}

function wkCompany(): Company
{
    return (new Company)->forceFill(['subdomain' => 'pilotco', 'api_token' => 'tok_123']);
}

function wkClient(): WorkableClient
{
    return new WorkableClient(app(\Illuminate\Http\Client\Factory::class));
}

beforeEach(fn () => config(['workable.fake' => false]));

it('sends bearer auth and the published query', function () {
    Http::fake(['pilotco.workable.com/spi/v3/jobs*' => Http::response(wkFixture('jobs_published.json'))]);

    wkClient()->listPublishedJobs(wkCompany());

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer tok_123')
        && str_contains($r->url(), 'state=published')
        && str_contains($r->url(), 'limit=100'));
});

it('follows paging.next across pages', function () {
    Http::fake([
        'pilotco.workable.com/spi/v3/jobs*since_id=a1b2c3d4*' => Http::response(wkFixture('jobs_page2.json')),
        'pilotco.workable.com/spi/v3/jobs*' => Http::response(wkFixture('jobs_page1.json')),
    ]);

    $jobs = wkClient()->listPublishedJobs(wkCompany());

    expect($jobs)->toHaveCount(2);
    expect($jobs[0]->externalId)->toBe('a1b2c3d4');
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'since_id=a1b2c3d4')
        && $r->hasHeader('Authorization', 'Bearer tok_123'));
});

it('stops when paging.next is null', function () {
    Http::fake(['pilotco.workable.com/spi/v3/jobs*' => Http::response(wkFixture('jobs_published.json'))]);

    wkClient()->listPublishedJobs(wkCompany());

    Http::assertSentCount(1);
});

it('maps status codes to exceptions', function (int $status, array $headers, string $class) {
    Http::fake(['pilotco.workable.com/*' => Http::response([], $status, $headers)]);

    $e = null;
    try {
        wkClient()->listPublishedJobs(wkCompany());
    } catch (Throwable $t) {
        $e = $t;
    }

    expect($e)->toBeInstanceOf($class);
    if ($e instanceof RateLimited) {
        expect($e->retryAfter())->toBe(45);
    }
})->with([
    '401' => [401, [], CredentialsRejected::class],
    '403' => [403, [], CredentialsRejected::class],
    '404' => [404, [], JobNotFound::class],
    '429' => [429, ['Retry-After' => '45'], RateLimited::class],
    '500' => [500, [], TransientFailure::class],
]);

it('maps 422 on create to PayloadRejected with validation errors', function () {
    Http::fake(['pilotco.workable.com/spi/v3/jobs/SENIO001/candidates' => Http::response(wkFixture('candidate_422.json'), 422)]);

    try {
        wkClient()->createCandidate(wkCompany(), 'SENIO001', ['candidate' => []]);
        $this->fail('expected PayloadRejected');
    } catch (PayloadRejected $e) {
        expect($e->validationErrors())->toHaveKey('email');
    }
});

it('posts create-candidate to the shortcode and returns the id', function () {
    Http::fake(['pilotco.workable.com/spi/v3/jobs/SENIO001/candidates' => Http::response(wkFixture('candidate_created.json'), 201)]);

    $res = wkClient()->createCandidate(wkCompany(), 'SENIO001', ['candidate' => ['name' => 'A']]);

    expect($res->status)->toBe(201)->and($res->candidateId)->toBe('cand_123');
    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && $r->url() === 'https://pilotco.workable.com/spi/v3/jobs/SENIO001/candidates');
});

it('attempts the POST exactly once', function () {
    Http::fake(['pilotco.workable.com/spi/v3/jobs/SENIO001/candidates' => Http::response([], 500)]);

    expect(fn () => wkClient()->createCandidate(wkCompany(), 'SENIO001', []))->toThrow(AmbiguousOutcome::class);
    Http::assertSentCount(1);
});

it('serves fixtures in fake mode without network', function () {
    config(['workable.fake' => true]);
    Http::fake();

    $c = wkCompany();
    expect(wkClient()->listPublishedJobs($c))->not->toBeEmpty();
    expect(wkClient()->getJob($c, 'SENIO001')->shortcode)->toBe('SENIO001');
    expect(wkClient()->getApplicationForm($c, 'SENIO001')->formFields)->not->toBeEmpty();
    expect(wkClient()->createCandidate($c, 'SENIO001', [])->candidateId)->toBe('cand_fake');
    Http::assertNothingSent();
});

it('does not follow paging.next to a different host', function () {
    $page = wkFixture('jobs_page1.json');
    $page['paging']['next'] = 'https://evil.example.com/spi/v3/jobs?since_id=x';
    Http::fake(['*' => Http::response($page)]);

    $jobs = wkClient()->listPublishedJobs(wkCompany());

    expect($jobs)->toHaveCount(count($page['jobs']));
    Http::assertSentCount(1);
    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'evil.example.com'));
});

it('terminates when paging.next points to itself', function () {
    $page = wkFixture('jobs_page1.json');
    $page['paging']['next'] = 'https://pilotco.workable.com/spi/v3/jobs?state=published&limit=100&include_fields=description%2Cfull_description%2Crequirements%2Cbenefits';
    Http::fake(['*' => Http::response($page)]);

    wkClient()->listPublishedJobs(wkCompany());

    Http::assertSentCount(1);
});

it('maps a POST timeout to AmbiguousOutcome', function () {
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out'));

    expect(fn () => wkClient()->createCandidate(wkCompany(), 'SENIO001', []))->toThrow(AmbiguousOutcome::class);
});

it('maps a POST connect failure to TransientFailure', function () {
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 7: Failed to connect'));

    expect(fn () => wkClient()->createCandidate(wkCompany(), 'SENIO001', []))->toThrow(TransientFailure::class);
});

it('maps unmapped 4xx to a non-transient error', function () {
    Http::fake(['*' => Http::response([], 400)]);

    $e = null;
    try {
        wkClient()->getJob(wkCompany(), 'SENIO001');
    } catch (Throwable $t) {
        $e = $t;
    }
    expect($e)->toBeInstanceOf(\App\Integrations\Workable\Exceptions\WorkableException::class)
        ->and($e)->not->toBeInstanceOf(TransientFailure::class);

    expect(fn () => wkClient()->createCandidate(wkCompany(), 'SENIO001', []))->toThrow(PayloadRejected::class);
});
