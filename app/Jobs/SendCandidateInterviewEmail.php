<?php

namespace App\Jobs;

use App\Services\CandidateMessageService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class SendCandidateInterviewEmail implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public int $tries = 4;

    public int $timeout = 30;

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $messageId, public readonly int $organizationId)
    {
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(CandidateMessageService $messages): void
    {
        $messages->deliver($this->messageId, $this->organizationId);
    }

    public function uniqueId(): string
    {
        return 'candidate-interview-email:'.$this->organizationId.':'.$this->messageId;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->uniqueId()))->releaseAfter(30)->expireAfter(90)];
    }

    public function failed(Throwable $exception): void
    {
        app(CandidateMessageService::class)->markFailed($this->messageId, $this->organizationId, $exception);
    }
}
