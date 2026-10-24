<?php

namespace App\Actions\Workable;

use App\Integrations\Workable\DTO\JobPostData;
use App\Integrations\Workable\Exceptions\WorkableException;
use App\Integrations\Workable\WorkableClient;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\SyncRun;
use Illuminate\Support\Facades\Cache;

/**
 * Reconciles a company's published Workable jobs into job_postings.
 *
 * Idempotent (an unchanged second run changes nothing) and fail-safe: jobs are
 * only closed ("swept") after a fully successful fetch, never after a failed
 * fetch or a suspicious empty response.
 */
class SyncCompanyJobs
{
    public function __construct(private WorkableClient $client) {}

    public function __invoke(Company $company): SyncRun
    {
        return Cache::lock("workable:sync:{$company->id}", 900)
            ->block(30, fn () => $this->sync($company));
    }

    private function sync(Company $company): SyncRun
    {
        $startedAt = now();
        $run = SyncRun::start($company);

        try {
            return $this->reconcile($company, $run, $startedAt);
        } catch (\Throwable $e) {
            // Unexpected crash (WorkableException is handled inside and returns):
            // never leave the seeded 'ok' row looking like a successful run.
            $run->finish(['status' => 'failed', 'error' => $e::class]);

            throw $e;
        }
    }

    private function reconcile(Company $company, SyncRun $run, \Illuminate\Support\Carbon $startedAt): SyncRun
    {
        try {
            $jobs = $this->client->listPublishedJobs($company);
        } catch (WorkableException $e) {
            $run->finish(['status' => 'failed', 'error' => $e::class]);

            return $run;
        }

        if ($jobs->isEmpty()) {
            $open = $company->jobPostings()->where('status', 'open')->count();
            if ($open >= (int) config('workable.sync_empty_guard_min_open')) {
                $run->finish(['status' => 'suspicious', 'fetched' => 0]);

                return $run;
            }
        }

        $created = 0;
        $updated = 0;

        foreach ($jobs as $job) {
            $posting = JobPosting::firstOrNew([
                'company_id' => $company->id,
                'shortcode' => $job->shortcode,
            ]);
            $exists = $posting->exists;

            $posting->fill($this->attributes($job));
            $posting->last_seen_at = $startedAt;
            $posting->status = 'open';
            $posting->closed_at = null;

            $hash = sha1((string) $job->contentHtml);
            if (! $exists || $posting->content_hash !== $hash) {
                $posting->content_html = $job->contentHtml;
                $posting->content_hash = $hash;
            }

            // last_seen_at moves every run; it alone does not count as an update.
            $changed = array_diff(array_keys($posting->getDirty()), ['last_seen_at']) !== [];
            $posting->save();

            if (! $exists) {
                $created++;
            } elseif ($changed) {
                $updated++;
            }
        }

        $closed = $company->jobPostings()
            ->where('status', 'open')
            ->where('last_seen_at', '<', $startedAt)
            ->update(['status' => 'closed', 'closed_at' => now()]);

        $company->update(['last_synced_at' => now()]);

        $run->finish([
            'status' => 'ok',
            'fetched' => $jobs->count(),
            'created' => $created,
            'updated' => $updated,
            'closed' => $closed,
        ]);

        return $run;
    }

    private function attributes(JobPostData $job): array
    {
        return [
            'external_id' => $job->externalId,
            'title' => $job->title,
            'full_title' => $job->fullTitle,
            'state' => $job->state,
            'department' => $job->department,
            'workplace_type' => $job->workplaceType,
            'url' => $job->url,
            'application_url' => $job->applicationUrl,
            'shortlink' => $job->shortlink,
            'location' => $job->location,
            'locations' => $job->locations,
            'salary' => $job->salary,
            'keywords' => $job->keywords,
            'external_updated_at' => $job->externalUpdatedAt,
        ];
    }
}
