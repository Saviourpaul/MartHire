<?php

namespace App\Jobs;

use App\Models\SecurityChallenge;
use App\Models\User;
use App\Notifications\SecurityChallengeCode;
use App\Services\SecurityChallengeService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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

class SendSecurityChallengeEmail implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 30;

    public int $uniqueFor = 900;

    public function __construct(public string $challengeId, #[SensitiveParameter] public string $code) {}

    public function uniqueId(): string
    {
        return 'settings-security:'.$this->challengeId;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    /**
     * Execute the job.
     */
    public function handle(SecurityChallengeService $challenges): void
    {
        $challenge = SecurityChallenge::query()->find($this->challengeId);
        if (! $challenge) {
            return;
        }

        try {
            DB::transaction(function () use ($challenge, $challenges): void {
                $user = User::query()->lockForUpdate()->find($challenge->user_id);
                $challenge = SecurityChallenge::query()->lockForUpdate()->find($this->challengeId);
                if (! $user || ! $challenge || ! $user->isActive() || ! $user->hasVerifiedEmail()
                    || $challenge->consumed_at !== null || $challenge->sent_at !== null || $challenge->expires_at->isPast()
                    || $challenge->authentication_version !== (int) $user->authentication_version
                    || ! hash_equals($challenge->email_hash, $challenges->fingerprint($user->email))
                    || ! hash_equals((string) $challenge->code_hash, $challenges->codeHash($challenge->id, $this->code))) {
                    return;
                }

                $name = (string) config('mail.default');
                $this->assertPrivateMailer($name);
                $mailer = Mail::mailer($name);
                $originalTransport = $mailer->getSymfonyTransport();
                try {
                    $mailer->setSymfonyTransport($this->privateTransport($name));
                    $user->notify(new SecurityChallengeCode($this->code, $challenge->purpose));
                } finally {
                    $mailer->setSymfonyTransport($originalTransport);
                }
                $challenge->update(['sent_at' => now(), 'failed_at' => null]);
            });
        } catch (Throwable) {
            SecurityChallenge::query()->whereKey($this->challengeId)->whereNull('sent_at')->whereNull('consumed_at')->update(['failed_at' => now()]);
            throw new RuntimeException('The security confirmation email could not be delivered.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        SecurityChallenge::query()->whereKey($this->challengeId)->whereNull('sent_at')->update(['failed_at' => now(), 'code_hash' => null]);
        Log::error('Security confirmation delivery failed.', ['challenge_id' => $this->challengeId]);
    }

    /** @param list<string> $visited */
    private function assertPrivateMailer(string $name, array $visited = []): void
    {
        $mailer = config('mail.mailers.'.$name);
        if (! is_array($mailer) || in_array($name, $visited, true) || ! is_string($mailer['transport'] ?? null) || $mailer['transport'] === 'log') {
            throw new RuntimeException('A private mail transport is required.');
        }
        if (in_array($mailer['transport'], ['failover', 'roundrobin'], true)) {
            $children = $mailer['mailers'] ?? [];
            if (! is_array($children) || $children === []) {
                throw new RuntimeException('A private mail transport is required.');
            }
            foreach ($children as $child) {
                if (! is_string($child)) {
                    throw new RuntimeException('A private mail transport is required.');
                }
                $this->assertPrivateMailer($child, [...$visited, $name]);
            }
        }
    }

    private function privateTransport(string $name): TransportInterface
    {
        $mailer = config('mail.mailers.'.$name);
        if (! in_array($mailer['transport'], ['failover', 'roundrobin'], true)) {
            return Mail::mailer($name)->getSymfonyTransport();
        }
        $children = array_map(fn (string $child): TransportInterface => $this->privateTransport($child), $mailer['mailers']);

        return $mailer['transport'] === 'failover'
            ? new FailoverTransport($children, (int) ($mailer['retry_after'] ?? 60), new NullLogger)
            : new RoundRobinTransport($children, (int) ($mailer['retry_after'] ?? 60), new NullLogger);
    }
}
