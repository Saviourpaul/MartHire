<?php

namespace App\Jobs;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendVerificationEmail implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * A delivery can fail temporarily when the mail provider is unavailable.
     */
    public int $tries = 4;

    /**
     * Keep the worker timeout below the queue connection's retry_after value.
     */
    public int $timeout = 30;

    /**
     * Avoid an orphaned unique lock if a worker dies unexpectedly.
     */
    public int $uniqueFor = 3600;

    public function __construct(public int $userId) {}

    public function uniqueId(): string
    {
        return "verification-email:{$this->userId}";
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHour();
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("verification-email:{$this->userId}"))
                ->releaseAfter(30)
                ->expireAfter(300),
        ];
    }

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if (! $user || $user->hasVerifiedEmail()) {
            return;
        }

        // Do not call User::sendEmailVerificationNotification() here: that method
        // dispatches this job and would create a recursion loop.
        $user->notify(new VerifyEmail);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Verification email delivery failed permanently.', [
            'user_id' => $this->userId,
            'exception' => $exception,
        ]);
    }
}
