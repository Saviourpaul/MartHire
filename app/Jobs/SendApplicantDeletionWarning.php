<?php

namespace App\Jobs;

use App\Models\AccountDeletionLifecycle;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendApplicantDeletionWarning implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public int $lifecycleId) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function handle(): void
    {
        $context = DB::transaction(function (): ?array {
            $initialLifecycle = AccountDeletionLifecycle::query()->find($this->lifecycleId);
            if (! $initialLifecycle || $initialLifecycle->status !== 'scheduled' || ! $initialLifecycle->active_user_id) {
                return null;
            }

            $userId = (int) $initialLifecycle->active_user_id;
            $user = User::query()->lockForUpdate()->find($userId);
            $lifecycle = AccountDeletionLifecycle::query()->lockForUpdate()->find($this->lifecycleId);
            if (! $lifecycle || $lifecycle->status !== 'scheduled' || (int) $lifecycle->active_user_id !== $userId) {
                return null;
            }

            if (! $user || ! $user->isApplicant() || ! $user->isActive() || ! $user->last_login_at
                || ! $lifecycle->last_login_at_snapshot
                || $user->last_login_at->greaterThan($lifecycle->last_login_at_snapshot)) {
                $lifecycle->forceFill([
                    'active_user_id' => null,
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'warning_status' => $lifecycle->warning_sent_at ? 'sent' : 'cancelled',
                ])->save();

                return null;
            }

            $lifecycle->forceFill([
                'warning_status' => 'sending',
                'warning_attempts' => (int) $lifecycle->warning_attempts + 1,
                'warning_error' => null,
            ])->save();

            return [
                'email' => $user->email,
                'name' => trim($user->first_name.' '.$user->last_name),
                'last_login_at' => $user->last_login_at,
                'inactivity_days' => max(2, (int) config('account_deletion.inactivity_days', 30)),
                'scheduled_at' => $lifecycle->scheduled_deletion_at,
                'reference' => $lifecycle->reference,
            ];
        }, 3);

        if (! $context) {
            return;
        }

        try {
            $scheduledAt = $context['scheduled_at'];
            Mail::raw(
                "Hello {$context['name']},\n\nOur records show no successful sign-in since {$context['last_login_at']->format('j F Y')}. Under the {$context['inactivity_days']}-day inactivity policy, your applicant account is scheduled for deletion on {$scheduledAt->format('j F Y')}. To keep your account, sign in before that date. A successful sign-in cancels the deletion schedule.\n\nSign in: ".url('/login')."\n\nDeletion reference: {$context['reference']}\n\nIf you need help, contact support.",
                function ($message) use ($context): void {
                    $message->to($context['email'])
                        ->subject('Action needed: keep your applicant account');
                }
            );

            DB::transaction(function (): void {
                $lifecycle = AccountDeletionLifecycle::query()->lockForUpdate()->find($this->lifecycleId);
                if (! $lifecycle || $lifecycle->status !== 'scheduled') {
                    return;
                }

                $sentAt = now();
                $data = [
                    'warning_status' => 'sent',
                    'warning_sent_at' => $sentAt,
                    'warning_error' => null,
                ];
                $inactivityDays = max(2, (int) config('account_deletion.inactivity_days', 30));
                $warningDays = min($inactivityDays - 1, max(1, (int) config('account_deletion.warning_days', 7)));
                if ($sentAt->greaterThan($lifecycle->scheduled_deletion_at->copy()->subDays($warningDays))) {
                    $data['scheduled_deletion_at'] = $sentAt->copy()->addDays($warningDays);
                }
                $lifecycle->forceFill($data)->save();
            }, 3);
        } catch (Throwable $exception) {
            AccountDeletionLifecycle::query()->whereKey($this->lifecycleId)->where('status', 'scheduled')->update([
                'warning_status' => 'failed',
                'warning_error' => 'The warning email could not be sent; another attempt is pending.',
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        AccountDeletionLifecycle::query()->whereKey($this->lifecycleId)->where('status', 'scheduled')->update([
            'warning_status' => 'failed',
            'warning_error' => 'The warning email could not be sent; automatic retries were exhausted.',
        ]);
    }
}
