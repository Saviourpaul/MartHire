<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Jobs\SendVerificationEmail;
use App\Jobs\SendWelcomeEmail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;
use Throwable;

class EmailVerificationService
{
    public function sendCode(User $user): bool
    {
        /** @var array{user_id: int, challenge_id: string, email: string, code: string}|null $delivery */
        $delivery = DB::transaction(function () use ($user): ?array {
            $lockedUser = User::query()->lockForUpdate()->find($user->getKey());

            if (! $lockedUser || $lockedUser->isSuspended() || $lockedUser->hasVerifiedEmail()) {
                return null;
            }

            $cooldown = $this->resendCooldown($lockedUser);

            if ($cooldown > 0) {
                throw ValidationException::withMessages([
                    'verification' => "Please wait {$cooldown} seconds before requesting another code.",
                ]);
            }

            do {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $hash = $this->codeHash($lockedUser, $code);
            } while (hash_equals((string) $lockedUser->email_verification_code_hash, $hash));

            $challengeId = (string) Str::uuid();
            $now = now();

            $lockedUser->forceFill([
                'email_verification_code_hash' => $hash,
                'email_verification_challenge_id' => $challengeId,
                'email_verification_expires_at' => $now->copy()->addMinutes((int) config('auth.verification.expire', 15)),
                'email_verification_requested_at' => $now,
                'email_verification_sent_at' => null,
                'email_verification_failed_at' => null,
                'email_verification_attempts' => 0,
            ])->save();

            return [
                'user_id' => (int) $lockedUser->getKey(),
                'challenge_id' => $challengeId,
                'email' => $lockedUser->getEmailForVerification(),
                'code' => $code,
            ];
        });

        if (! $delivery) {
            return false;
        }

        try {
            SendVerificationEmail::dispatch(
                $delivery['user_id'],
                $delivery['challenge_id'],
                $delivery['email'],
                $delivery['code'],
            )->afterCommit();
        } catch (Throwable) {
            User::query()->whereKey($delivery['user_id'])
                ->where('email_verification_challenge_id', $delivery['challenge_id'])
                ->update(['email_verification_failed_at' => now()]);

            return false;
        }

        return true;
    }

    public function resendCooldown(User $user): int
    {
        if (! $user->email_verification_requested_at) {
            return 0;
        }

        return max(0, $user->email_verification_requested_at->getTimestamp()
            + (int) config('auth.verification.resend_cooldown', 60) - now()->getTimestamp());
    }

    public function verifyCode(User $user, #[SensitiveParameter] string $code): ?User
    {
        $result = DB::transaction(function () use ($user, $code): User|string|null {
            $lockedUser = User::query()->lockForUpdate()->find($user->getKey());

            if (! $lockedUser || $lockedUser->isSuspended()) {
                return null;
            }

            if ($lockedUser->hasVerifiedEmail()) {
                return $lockedUser;
            }

            $maxAttempts = (int) config('auth.verification.max_attempts', 5);

            if ($lockedUser->email_verification_attempts >= $maxAttempts) {
                return 'Too many incorrect attempts. Please request a new verification code.';
            }

            if (! $lockedUser->email_verification_code_hash || ! $lockedUser->email_verification_expires_at) {
                return 'Request a verification code before trying to verify your email.';
            }

            if ($lockedUser->email_verification_expires_at->lessThanOrEqualTo(now())) {
                return 'Your verification code has expired. Please request a new code.';
            }

            if (! hash_equals($lockedUser->email_verification_code_hash, $this->codeHash($lockedUser, $code))) {
                $attempts = $lockedUser->email_verification_attempts + 1;
                $lockedUser->forceFill([
                    'email_verification_attempts' => $attempts,
                    ...($attempts >= $maxAttempts ? [
                        'email_verification_code_hash' => null,
                        'email_verification_challenge_id' => null,
                        'email_verification_expires_at' => null,
                    ] : []),
                ])->save();

                return $attempts >= $maxAttempts
                    ? 'Too many incorrect attempts. Please request a new verification code.'
                    : 'The verification code is incorrect. Please try again.';
            }

            return $this->activate($lockedUser);
        });

        if (is_string($result)) {
            throw ValidationException::withMessages(['code' => $result]);
        }

        return $result;
    }

    public function verifyTrustedEmail(User $user, string $verifiedEmail): ?User
    {
        return DB::transaction(function () use ($user, $verifiedEmail): ?User {
            $lockedUser = User::query()->lockForUpdate()->find($user->getKey());

            if (! $lockedUser || $lockedUser->isSuspended()
                || strcasecmp($lockedUser->getEmailForVerification(), $verifiedEmail) !== 0) {
                return null;
            }

            return $this->activate($lockedUser);
        });
    }

    private function codeHash(User $user, #[SensitiveParameter] string $code): string
    {
        return hash_hmac('sha256', $user->getKey().'|'.$user->getEmailForVerification().'|'.$code, (string) config('app.key'));
    }

    private function activate(User $user): User
    {
        $verifiedNow = ! $user->hasVerifiedEmail();
        $now = now();
        $queueWelcomeEmail = $verifiedNow && $user->welcome_email_queued_at === null;

        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? $now,
            'status' => UserStatus::Active,
            'approved_at' => $user->approved_at ?? $now,
            'suspended_at' => null,
            'email_verification_code_hash' => null,
            'email_verification_challenge_id' => null,
            'email_verification_expires_at' => null,
            'email_verification_requested_at' => null,
            'email_verification_sent_at' => null,
            'email_verification_failed_at' => null,
            'email_verification_attempts' => 0,
            ...($queueWelcomeEmail ? ['welcome_email_queued_at' => $now] : []),
        ]);

        if ($user->isDirty()) {
            $user->save();
        }

        if ($queueWelcomeEmail) {
            SendWelcomeEmail::dispatch($user->getKey())
                ->delay($now->copy()->addSeconds(30))
                ->afterCommit();
        }

        if ($verifiedNow) {
            DB::afterCommit(fn () => event(new Verified($user)));
        }

        return $user;
    }
}
