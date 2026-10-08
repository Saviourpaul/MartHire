<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Jobs\SendWelcomeEmail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;

class EmailVerificationService
{
    /**
     * Verify and activate a pending user as one database transition.
     *
     * A null result means the account was suspended while pending and must not
     * be reactivated merely because an old verification link is still valid.
     */
    public function verify(User $user): ?User
    {
        $verifiedNow = false;

        /** @var User|null $verifiedUser */
        $verifiedUser = DB::transaction(function () use ($user, &$verifiedNow): ?User {
            $lockedUser = User::query()
                ->lockForUpdate()
                ->find($user->getKey());

            if (! $lockedUser || $lockedUser->isSuspended()) {
                return null;
            }

            if ($lockedUser->hasVerifiedEmail()) {
                // Repair an interrupted legacy state without treating it as a
                // new verification or sending a second welcome email.
                if (! $lockedUser->isActive()) {
                    $lockedUser->forceFill([
                        'status' => UserStatus::Active,
                        'approved_at' => $lockedUser->approved_at ?? now(),
                        'suspended_at' => null,
                    ])->save();
                }

                return $lockedUser;
            }

            $now = now();
            $queueWelcomeEmail = is_null($lockedUser->welcome_email_queued_at);

            $lockedUser->forceFill([
                'email_verified_at' => $now,
                'status' => UserStatus::Active,
                'approved_at' => $lockedUser->approved_at ?? $now,
                'suspended_at' => null,
                'welcome_email_queued_at' => $lockedUser->welcome_email_queued_at ?? $now,
            ])->save();

            if ($queueWelcomeEmail) {
                SendWelcomeEmail::dispatch($lockedUser->getKey())
                    ->delay($now->copy()->addSeconds(30))
                    ->afterCommit();
            }

            $verifiedNow = true;

            return $lockedUser;
        });

        if ($verifiedNow && $verifiedUser) {
            event(new Verified($verifiedUser));
        }

        return $verifiedUser;
    }
}
