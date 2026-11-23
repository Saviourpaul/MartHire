<?php

namespace App\Jobs\Workable;

use App\Actions\Workable\PayloadBuilder;
use App\Actions\Workable\ResolveApplicationSchema;
use App\Integrations\Workable\Exceptions\AmbiguousOutcome;
use App\Integrations\Workable\Exceptions\CredentialsRejected;
use App\Integrations\Workable\Exceptions\JobNotFound;
use App\Integrations\Workable\Exceptions\PayloadRejected;
use App\Integrations\Workable\Exceptions\RateLimited;
use App\Integrations\Workable\Exceptions\TransientFailure;
use App\Integrations\Workable\WorkableClient;
use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * The application outbox (design §7.5). The job_applications row already exists
 * (status queued) before this runs, so a worker crash never loses or duplicates
 * an application. At-most-once: the POST is attempted once per run and is never
 * auto-retried after it may have reached Workable — an AmbiguousOutcome parks
 * the application for a human instead.
 */
class SubmitJobApplicationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 75;

    public ?string $dispatchToken = null;

    public function __construct(public JobApplication $application, ?string $dispatchToken = null)
    {
        $this->dispatchToken = $dispatchToken;
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 600, 3600];
    }

    public function handle(WorkableClient $client, ResolveApplicationSchema $resolve, PayloadBuilder $builder): void
    {
        $application = $this->claimApplication();
        if ($application === null) {
            return;
        }

        $posting = $application->jobPosting;
        $company = $posting?->company;
        if ($posting === null || $company === null) {
            $this->settle($application, ['status' => 'failed_permanent', 'error_code' => 'missing_posting']);

            return;
        }

        $payload = $application->payload ?? [];
        $schema = ($resolve)($company, $posting);
        $data = $payload['data'] ?? [];
        $files = $this->loadFiles($payload);
        $body = $builder->build($schema, $data, $files);

        try {
            $result = $client->createCandidate($company, $posting->shortcode, $body);
        } catch (CredentialsRejected) {
            // The employer's key is bad: stop native apply for the whole company, keep the payload for ops.
            $company->update(['credentials_status' => 'invalid', 'apply_mode' => 'redirect']);
            $this->settle($application, ['status' => 'failed_credentials', 'error_code' => 'credentials_rejected']);

            return;
        } catch (JobNotFound) {
            $posting->update(['status' => 'closed', 'closed_at' => now()]);
            $this->settle($application, ['status' => 'failed_job_closed', 'error_code' => 'job_closed']);
            $this->purge($application);

            return;
        } catch (PayloadRejected $e) {
            // Permanent — our bug or a duplicate. Record PII-free field keys only.
            $this->settle($application, [
                'status' => 'failed_permanent',
                'error_code' => array_key_exists('email', $e->validationErrors) ? 'duplicate_or_invalid_email' : 'payload_rejected',
                'response_excerpt' => substr(implode(',', array_keys($e->validationErrors)), 0, 250),
            ]);

            return;
        } catch (AmbiguousOutcome) {
            // The request may already have created a candidate — never auto-retry.
            $this->settle($application, ['status' => 'needs_review', 'error_code' => 'ambiguous_outcome']);

            return;
        } catch (RateLimited $e) {
            $this->scheduleRetry($application, $e->retryAfter ?? 60);

            return;
        } catch (TransientFailure) {
            $this->scheduleRetry($application, $this->backoff()[$application->attempts - 1] ?? 3600);

            return;
        }

        // Success (2xx): record, email later, purge sensitive data now.
        $this->settle($application, [
            'status' => 'submitted',
            'submitted_at' => now(),
            'http_status' => $result->status,
            'workable_candidate_id' => $result->candidateId,
            'error_code' => null,
        ]);
        $this->purge($application);
    }

    /** Retries exhausted — leave a clear terminal state (unless already terminal). */
    public function failed(?Throwable $e): void
    {
        $application = $this->application->fresh();
        if ($application !== null && $application->status === 'submitting' && $application->dispatch_token === $this->dispatchToken) {
            $this->settle($application, ['status' => 'failed_retryable', 'error_code' => 'retries_exhausted']);
        }
    }

    private function claimApplication(): ?JobApplication
    {
        $application = $this->application->fresh();
        if ($application === null) {
            return null;
        }

        $token = $this->dispatchToken;
        if ($application->dispatch_token !== $token) {
            return null;
        }

        $now = now();
        $leaseSeconds = max($this->timeout + 30, (int) config('workable.application.processing_lease_seconds', 180));
        $leaseUntil = $now->copy()->addSeconds($leaseSeconds);

        if ($application->status === 'queued') {
            $claimed = JobApplication::query()
                ->whereKey($application->id)
                ->where('status', 'queued')
                ->where(function ($query) use ($token): void {
                    $token === null
                        ? $query->whereNull('dispatch_token')
                        : $query->where('dispatch_token', $token);
                })
                ->where(function ($query) use ($now): void {
                    $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now);
                })
                ->update([
                    'status' => 'submitting',
                    'attempts' => \Illuminate\Support\Facades\DB::raw('attempts + 1'),
                    'processing_lease_until' => $leaseUntil,
                ]);

            return $claimed === 1 ? $application->fresh() : null;
        }

        // A redelivery of this exact queue message may resume after its lease
        // expires. A duplicate dispatch with another token can never enter.
        if ($application->status !== 'submitting' || max(1, (int) $this->attempts()) <= 1) {
            return null;
        }

        $activeLease = $application->processing_lease_until;
        if ($activeLease !== null && $activeLease->isFuture()) {
            $this->release(max(1, $activeLease->getTimestamp() - $now->getTimestamp()));

            return null;
        }

        $claimed = JobApplication::query()
            ->whereKey($application->id)
            ->where('status', 'submitting')
            ->where(function ($query) use ($token): void {
                $token === null
                    ? $query->whereNull('dispatch_token')
                    : $query->where('dispatch_token', $token);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('processing_lease_until')->orWhere('processing_lease_until', '<=', $now);
            })
            ->update(['processing_lease_until' => $leaseUntil]);

        return $claimed === 1 ? $application->fresh() : null;
    }

    /** @param array<string, mixed> $attributes */
    private function settle(JobApplication $application, array $attributes): void
    {
        $application->update($attributes + [
            'dispatch_token' => null,
            'next_attempt_at' => null,
            'processing_lease_until' => null,
        ]);
    }

    private function scheduleRetry(JobApplication $application, int $delaySeconds): void
    {
        $delaySeconds = max(1, min(86400, $delaySeconds));
        $application->update([
            'status' => 'queued',
            'dispatch_token' => null,
            'dispatch_attempted_at' => null,
            'next_attempt_at' => now()->addSeconds($delaySeconds),
            'processing_lease_until' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, array{name: string, data: string}>
     */
    private function loadFiles(array $payload): array
    {
        $disk = Storage::disk(config('workable.application.disk', 'local'));
        $files = [];
        foreach (($payload['files'] ?? []) as $field => $ref) {
            if (isset($ref['path'])) {
                $files[$field] = ['name' => $ref['name'] ?? 'upload', 'data' => base64_encode($disk->get($ref['path']))];
            }
        }

        return $files;
    }

    private function purge(JobApplication $application): void
    {
        $disk = Storage::disk(config('workable.application.disk', 'local'));
        foreach (($application->payload['files'] ?? []) as $ref) {
            if (isset($ref['path'])) {
                $disk->delete($ref['path']);
            }
        }
        $application->update(['payload' => null]);
    }
}
