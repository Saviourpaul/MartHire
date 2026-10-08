<?php

namespace App\Jobs;

use App\Mail\WelcomeEmail;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendWelcomeEmail implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 4;

    public int $timeout = 30;

    public int $uniqueFor = 3600;

    public function __construct(public int $userId) {}

    public function uniqueId(): string
    {
        return "welcome-email:{$this->userId}";
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
            (new WithoutOverlapping("welcome-email:{$this->userId}"))
                ->releaseAfter(30)
                ->expireAfter(300),
        ];
    }

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if (! $user || ! $user->hasVerifiedEmail() || ! $user->isActive() || $user->welcome_email_sent_at) {
            return;
        }

        // Let transport failures escape. The queue worker will retry this job and,
        // after its final attempt, persist the failure to failed_jobs.
        Mail::to($user->email)->send(new WelcomeEmail($user));

        User::query()
            ->whereKey($user->getKey())
            ->whereNull('welcome_email_sent_at')
            ->update([
                'welcome_email_sent_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Welcome email delivery failed permanently.', [
            'user_id' => $this->userId,
            'exception' => $exception,
        ]);
    }
}
