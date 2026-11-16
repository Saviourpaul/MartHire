<?php

namespace App\Jobs;

use App\Services\AccountDataPurgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PurgeDeletedAccountFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 120;

    public function __construct(public int $lifecycleId) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function handle(AccountDataPurgeService $purges): void
    {
        $purges->purgeFilesForLifecycle($this->lifecycleId);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Deleted account file cleanup exhausted its queue retries.', [
            'lifecycle_id' => $this->lifecycleId,
            'exception' => $exception ? $exception::class : null,
        ]);
    }
}
