<?php

namespace App\Actions\Workable;

use App\Jobs\Workable\SubmitJobApplicationJob;
use App\Models\JobApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DispatchQueuedApplication
{
    public function dispatch(int $applicationId, bool $afterResponseWhenSynchronous = false): bool
    {
        $now = now();
        $staleBefore = $now->copy()->subMinutes(max(1, (int) config('workable.application.dispatch_stale_minutes', 15)));
        $token = (string) Str::uuid();

        $claimed = JobApplication::query()
            ->whereKey($applicationId)
            ->where('status', 'queued')
            ->where(function ($query) use ($now): void {
                $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now);
            })
            ->where(function ($query) use ($staleBefore): void {
                $query->whereNull('dispatch_attempted_at')->orWhere('dispatch_attempted_at', '<=', $staleBefore);
            })
            ->update([
                'dispatch_token' => $token,
                'dispatch_attempted_at' => $now,
                'dispatch_attempts' => DB::raw('dispatch_attempts + 1'),
            ]);

        if ($claimed !== 1) {
            return false;
        }

        $application = JobApplication::query()->find($applicationId);
        if (! $application) {
            return false;
        }

        try {
            if ($afterResponseWhenSynchronous && config('queue.default') === 'sync') {
                SubmitJobApplicationJob::dispatchAfterResponse($application, $token);
            } else {
                SubmitJobApplicationJob::dispatch($application, $token);
            }
        } catch (Throwable $exception) {
            Log::warning('A Workable application could not be queued; the durable outbox will retry it.', [
                'application_id' => $applicationId,
                'dispatch_attempt' => $application->dispatch_attempts,
                'exception' => $exception::class,
            ]);

            return false;
        }

        return true;
    }
}
