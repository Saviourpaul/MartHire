<?php

namespace App\Integrations\Workable;

use App\Integrations\Workable\DTO\ApplicationSchema;
use App\Integrations\Workable\DTO\JobPostData;
use App\Integrations\Workable\DTO\SubmissionResult;
use App\Integrations\Workable\Exceptions\AmbiguousOutcome;
use App\Integrations\Workable\Exceptions\CredentialsRejected;
use App\Integrations\Workable\Exceptions\JobNotFound;
use App\Integrations\Workable\Exceptions\PayloadRejected;
use App\Integrations\Workable\Exceptions\RateLimited;
use App\Integrations\Workable\Exceptions\TransientFailure;
use App\Integrations\Workable\Exceptions\WorkableException;
use App\Models\Company;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;

/**
 * Thin owned Workable API client. The only class that knows Workable URLs,
 * Bearer auth and raw response shapes. Never logs tokens or POST bodies.
 */
class WorkableClient
{
    private const MAX_PAGES = 100;

    public function __construct(private Factory $http) {}

    /** @return Collection<int, JobPostData> */
    public function listPublishedJobs(Company $company): Collection
    {
        if ($this->isFake()) {
            return $this->mapJobs($this->fixture('jobs_published.json'));
        }

        $jobs = collect();
        $url = $this->baseUrl($company).'/jobs?'.http_build_query([
            'state' => 'published',
            'limit' => 100,
            'include_fields' => 'description,full_description,requirements,benefits',
        ]);

        $host = parse_url($this->baseUrl($company), PHP_URL_HOST);
        $visited = [];

        while ($url && count($visited) < self::MAX_PAGES) {
            $visited[$url] = true;
            $json = $this->get($company, $url)->json() ?? [];
            $jobs = $jobs->merge($this->mapJobs($json));
            $next = data_get($json, 'paging.next');
            $url = null;

            // Only follow a next URL on the account's own host (never leak the token
            // elsewhere) and never revisit a URL.
            if (is_string($next) && $next !== ''
                && parse_url($next, PHP_URL_HOST) === $host
                && ! isset($visited[$next])) {
                $url = $next;
            }
        }

        return $jobs->values();
    }

    public function getJob(Company $company, string $shortcode): JobPostData
    {
        if ($this->isFake()) {
            return JobPostData::fromApi($this->fixture('job_detail.json'));
        }

        return JobPostData::fromApi($this->get($company, $this->baseUrl($company)."/jobs/{$shortcode}")->json() ?? []);
    }

    public function getApplicationForm(Company $company, string $shortcode): ApplicationSchema
    {
        if ($this->isFake()) {
            return ApplicationSchema::fromApi($this->fixture('application_form.json'));
        }

        return ApplicationSchema::fromApi(
            $this->get($company, $this->baseUrl($company)."/jobs/{$shortcode}/application_form")->json() ?? []
        );
    }

    public function createCandidate(Company $company, string $shortcode, array $payload): SubmissionResult
    {
        if ($this->isFake()) {
            return new SubmissionResult(201, 'cand_fake');
        }

        try {
            // No retry: a POST must never be repeated by the client.
            $res = $this->http($company)->post($this->baseUrl($company)."/jobs/{$shortcode}/candidates", $payload);
        } catch (ConnectionException $e) {
            // Only clearly pre-send failures (6: can't resolve host, 7: can't connect)
            // are safe to retry. Anything else may have happened after sending.
            if (preg_match('/cURL error (6|7)\b/', $e->getMessage())) {
                throw new TransientFailure('Could not connect to Workable.', 0, $e);
            }
            throw new AmbiguousOutcome('Workable submission failed after send; outcome unknown.', 0, $e);
        }

        $this->assertSuccessful($res, true);

        return new SubmissionResult($res->status(), data_get($res->json(), 'candidate.id'));
    }

    private function get(Company $company, string $url): Response
    {
        try {
            $res = $this->http($company)
                ->retry(3, 300, fn ($e) => $this->isTransient($e), throw: false)
                ->get($url);
        } catch (ConnectionException $e) {
            throw new TransientFailure('Could not connect to Workable.', 0, $e);
        }

        $this->assertSuccessful($res);

        return $res;
    }

    private function http(Company $company): PendingRequest
    {
        return $this->http
            ->withToken($company->api_token)
            ->withUserAgent(config('workable.user_agent'))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15);
    }

    private function baseUrl(Company $company): string
    {
        return sprintf(config('workable.host_template'), $company->subdomain, config('workable.api_version'));
    }

    private function isTransient(\Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }
        if ($e instanceof RequestException) {
            $status = $e->response->status();

            return $status === 429 || $status >= 500;
        }

        return false;
    }

    private function assertSuccessful(Response $res, bool $isSubmission = false): void
    {
        if ($res->successful()) {
            return;
        }

        $status = $res->status();

        throw match (true) {
            $status === 401, $status === 403 => new CredentialsRejected("Workable rejected credentials ({$status})."),
            $status === 404 => new JobNotFound('Workable resource not found.'),
            $status === 429 => new RateLimited((int) $res->header('Retry-After') ?: null, 'Workable rate limit hit.'),
            $isSubmission && $status === 422 => new PayloadRejected((array) data_get($res->json(), 'validation_errors', []), 'Workable rejected the payload.'),
            $status >= 400 && $status < 500 => $isSubmission
                ? new PayloadRejected([], "Workable rejected the submission ({$status}).")
                : new WorkableException("Workable request failed permanently ({$status})."),
            $isSubmission && $status >= 500 => new AmbiguousOutcome("Workable returned {$status} on submission; outcome unknown."),
            default => new TransientFailure("Workable request failed ({$status})."),
        };
    }

    /** @return Collection<int, JobPostData> */
    private function mapJobs(array $json): Collection
    {
        if (! isset($json['jobs']) || ! is_array($json['jobs'])) {
            throw new WorkableException('Workable jobs response is missing "jobs".');
        }

        return collect($json['jobs'])->map(fn (array $j) => JobPostData::fromApi($j))->values();
    }

    private function isFake(): bool
    {
        return (bool) config('workable.fake');
    }

    private function fixture(string $name): array
    {
        return json_decode(file_get_contents(base_path("tests/Fixtures/workable/{$name}")), true) ?? [];
    }
}
