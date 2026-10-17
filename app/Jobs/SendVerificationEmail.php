<?php

namespace App\Jobs;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\VerifyEmailCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Psr\Log\NullLogger;
use RuntimeException;
use SensitiveParameter;
use Symfony\Component\Mailer\Transport\FailoverTransport;
use Symfony\Component\Mailer\Transport\RoundRobinTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Throwable;

class SendVerificationEmail implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 4;

    public int $timeout = 30;

    public int $uniqueFor = 900;

    public function __construct(
        public int $userId,
        public string $challengeId,
        public string $email,
        #[SensitiveParameter] public string $code,
    ) {}

    public function uniqueId(): string
    {
        return 'verification-email:'.$this->userId.':'.($this->challengeId ?? 'legacy');
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("verification-email:{$this->userId}"))
                ->releaseAfter(30)
                ->expireAfter(60),
        ];
    }

    public function handle(): void
    {
        if (! isset($this->challengeId, $this->email, $this->code)) {
            return;
        }

        try {
            DB::transaction(function (): void {
                $user = User::query()->lockForUpdate()->find($this->userId);

                if (! $this->isCurrentChallenge($user)) {
                    return;
                }

                $this->sendCode($user);
                $user->forceFill([
                    'email_verification_sent_at' => now(),
                    'email_verification_failed_at' => null,
                ])->save();
            });
        } catch (Throwable) {
            $this->recordDeliveryFailure();

            throw new RuntimeException('The verification code could not be delivered.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->recordDeliveryFailure();

        Log::error('Verification code delivery failed permanently.', [
            'user_id' => $this->userId,
            'challenge_id' => $this->challengeId ?? null,
        ]);
    }

    private function isCurrentChallenge(?User $user): bool
    {
        return $user !== null
            && ! $user->hasVerifiedEmail()
            && ! $user->isSuspended()
            && $user->email === $this->email
            && $user->email_verification_challenge_id === $this->challengeId
            && $user->email_verification_code_hash !== null
            && $user->email_verification_sent_at === null
            && $user->email_verification_expires_at?->isFuture() === true;
    }

    private function sendCode(User $user): void
    {
        $name = (string) config('mail.default');
        $this->ensurePrivateMailer($name);

        if (! in_array(config('mail.mailers.'.$name.'.transport'), ['failover', 'roundrobin'], true)) {
            $user->notify(new VerifyEmailCode($this->code));

            return;
        }

        $mailer = Mail::mailer($name);
        $originalTransport = $mailer->getSymfonyTransport();

        try {
            $mailer->setSymfonyTransport($this->privateTransport($name));
            $user->notify(new VerifyEmailCode($this->code));
        } finally {
            $mailer->setSymfonyTransport($originalTransport);
        }
    }

    private function privateTransport(string $name): TransportInterface
    {
        $mailer = config('mail.mailers.'.$name);

        if (! in_array($mailer['transport'], ['failover', 'roundrobin'], true)) {
            return Mail::mailer($name)->getSymfonyTransport();
        }

        $children = array_map(fn (string $child): TransportInterface => $this->privateTransport($child), $mailer['mailers']);
        $retryAfter = (int) ($mailer['retry_after'] ?? 60);

        return $mailer['transport'] === 'failover'
            ? new FailoverTransport($children, $retryAfter, new NullLogger)
            : new RoundRobinTransport($children, $retryAfter, new NullLogger);
    }

    /**
     * @param  list<string>  $visited
     */
    private function ensurePrivateMailer(string $name, array $visited = []): void
    {
        $mailer = config('mail.mailers.'.$name);

        if (in_array($name, $visited, true) || ! is_array($mailer)) {
            throw new RuntimeException('The verification mail transport is unavailable.');
        }

        $transport = $mailer['transport'] ?? null;

        if (! is_string($transport) || $transport === 'log') {
            throw new RuntimeException('The verification mail transport is unavailable.');
        }

        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            $children = $mailer['mailers'] ?? [];

            if (! is_array($children) || $children === []) {
                throw new RuntimeException('The verification mail transport is unavailable.');
            }

            foreach ($children as $child) {
                if (! is_string($child)) {
                    throw new RuntimeException('The verification mail transport is unavailable.');
                }

                $this->ensurePrivateMailer($child, [...$visited, $name]);
            }
        }
    }

    private function recordDeliveryFailure(): void
    {
        if (! isset($this->challengeId, $this->email)) {
            return;
        }

        User::query()
            ->whereKey($this->userId)
            ->where('email', $this->email)
            ->where('email_verification_challenge_id', $this->challengeId)
            ->whereNull('email_verified_at')
            ->whereNull('email_verification_sent_at')
            ->whereNotNull('email_verification_code_hash')
            ->where('email_verification_expires_at', '>', now())
            ->where('status', '!=', UserStatus::Suspended->value)
            ->update(['email_verification_failed_at' => now()]);
    }
}
