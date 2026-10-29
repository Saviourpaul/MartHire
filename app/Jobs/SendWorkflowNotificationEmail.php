<?php

namespace App\Jobs;

use App\Models\NotificationEmailOutbox;
use App\Services\NotificationEmailService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendWorkflowNotificationEmail implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $uniqueFor = 120;

    public function __construct(public readonly int $outboxId) {}

    public function uniqueId(): string
    {
        return (string) $this->outboxId;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(NotificationEmailService $service): void
    {
        $service->deliver($this->outboxId);
    }

    public function failed(?Throwable $exception): void
    {
        NotificationEmailOutbox::query()->whereKey($this->outboxId)->whereIn('status', ['pending', 'processing'])
            ->update(['status' => 'pending', 'locked_until' => null, 'available_at' => now()->addMinutes(5), 'last_error' => 'Worker execution failed; the outbox will retry.']);
    }
}
