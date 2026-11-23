<?php

namespace App\Console\Commands\Workable;

use App\Actions\Workable\DispatchQueuedApplication;
use App\Models\JobApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchQueuedApplications extends Command
{
    protected $signature = 'workable:dispatch-applications {--batch=100 : Maximum pending submissions to dispatch}';

    protected $description = 'Dispatch and recover due Workable application outbox records';

    public function handle(DispatchQueuedApplication $dispatcher): int
    {
        $limit = max(1, min(1000, (int) $this->option('batch')));
        $staleBefore = now()->subMinutes(max(1, (int) config('workable.application.dispatch_stale_minutes', 15)));
        $ids = JobApplication::query()
            ->where('status', 'queued')
            ->where(function ($query): void {
                $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
            })
            ->where(function ($query) use ($staleBefore): void {
                $query->whereNull('dispatch_attempted_at')->orWhere('dispatch_attempted_at', '<=', $staleBefore);
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->pluck('id');

        $dispatched = 0;
        foreach ($ids as $id) {
            $dispatched += (int) $dispatcher->dispatch((int) $id);
        }

        $expiredLease = now();
        $legacyStaleBefore = $expiredLease->copy()->subMinutes(max(5, (int) ceil((int) config('workable.application.processing_lease_seconds', 180) / 60)));
        $interruptedIds = JobApplication::query()
            ->where('status', 'submitting')
            ->where(function ($query) use ($expiredLease, $legacyStaleBefore): void {
                $query->where('processing_lease_until', '<=', $expiredLease)
                    ->orWhere(function ($legacy) use ($legacyStaleBefore): void {
                        $legacy->whereNull('processing_lease_until')->where('updated_at', '<=', $legacyStaleBefore);
                    });
            })
            ->limit($limit)
            ->pluck('id');

        $parked = 0;
        foreach ($interruptedIds as $id) {
            $parked += JobApplication::query()->whereKey($id)->where('status', 'submitting')
                ->where(function ($query) use ($expiredLease, $legacyStaleBefore): void {
                    $query->where('processing_lease_until', '<=', $expiredLease)
                        ->orWhere(function ($legacy) use ($legacyStaleBefore): void {
                            $legacy->whereNull('processing_lease_until')->where('updated_at', '<=', $legacyStaleBefore);
                        });
                })
                ->update([
                    'status' => 'needs_review',
                    'error_code' => 'worker_interrupted',
                    'dispatch_token' => null,
                    'processing_lease_until' => null,
                ]);
        }

        if ($parked > 0) {
            Log::warning('Expired Workable application workers were parked for review.', ['count' => $parked]);
        }

        $this->info("{$dispatched} queued applications dispatched; {$parked} interrupted applications parked for review.");

        return self::SUCCESS;
    }
}
