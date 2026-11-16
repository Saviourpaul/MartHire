<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Jobs\SendApplicantDeletionWarning;
use App\Models\AccountDeletionLifecycle;
use App\Models\User;
use App\Services\AccountDataPurgeService;
use App\Services\AccountLifecycleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessApplicantInactivity extends Command
{
    protected $signature = 'accounts:process-inactivity {--limit= : Maximum accounts and lifecycle rows to inspect per run}';

    protected $description = 'Schedule warnings and remove applicant accounts after 30 days without a successful login.';

    public function handle(AccountLifecycleService $accounts, AccountDataPurgeService $purges): int
    {
        $inactivityDays = max(2, (int) config('account_deletion.inactivity_days', 30));
        $warningDays = min($inactivityDays - 1, max(1, (int) config('account_deletion.warning_days', 7)));
        $configuredLimit = $this->option('limit') === null ? config('account_deletion.batch_size', 5000) : $this->option('limit');
        $limit = max(1, min(50000, (int) $configuredLimit));
        $warningThresholdDays = max(1, $inactivityDays - $warningDays);
        // Keep re-dispatch behind the queue job's full retry/backoff window.
        $retryAfterMinutes = max(60, (int) config('account_deletion.warning_queue_retry_minutes', 60));
        $cutoff = now()->subDays($warningThresholdDays);
        $scheduled = 0;

        $users = User::query()->role(UserRole::Applicant)->active()
            ->whereNotNull('email_verified_at')
            ->whereNotNull('last_login_at')
            ->where('last_login_at', '<=', $cutoff)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('account_deletion_lifecycles')
                    ->whereColumn('account_deletion_lifecycles.active_user_id', 'users.id')
                    ->where('account_deletion_lifecycles.status', 'scheduled');
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($users as $candidate) {
            DB::transaction(function () use ($candidate, &$scheduled, $warningThresholdDays, $warningDays, $inactivityDays): void {
                $user = User::query()->lockForUpdate()->find($candidate->id);
                if (! $user || ! $user->isApplicant() || ! $user->isActive() || ! $user->email_verified_at
                    || ! $user->last_login_at || $user->last_login_at->greaterThan(now()->subDays($warningThresholdDays))) {
                    return;
                }

                $existing = AccountDeletionLifecycle::query()
                    ->where('active_user_id', $user->id)
                    ->where('status', 'scheduled')
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    if ($user->last_login_at->greaterThan($existing->last_login_at_snapshot)) {
                        $existing->forceFill([
                            'active_user_id' => null,
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                            'warning_status' => $existing->warning_sent_at ? 'sent' : 'cancelled',
                        ])->save();
                    }

                    return;
                }

                $minimumNoticeDate = now()->addDays($warningDays);
                $policyDate = $user->last_login_at->copy()->addDays($inactivityDays);
                $lifecycle = AccountDeletionLifecycle::query()->create([
                    'user_id' => $user->id,
                    'active_user_id' => $user->id,
                    'role_snapshot' => $user->role->value,
                    'reason' => 'inactive_30_days',
                    'status' => 'scheduled',
                    'last_login_at_snapshot' => $user->last_login_at,
                    'scheduled_deletion_at' => $policyDate->greaterThan($minimumNoticeDate) ? $policyDate : $minimumNoticeDate,
                    'warning_status' => 'queued',
                    'warning_queued_at' => now(),
                ]);
                $lifecycleId = (int) $lifecycle->id;
                DB::afterCommit(fn () => $this->dispatchWarning($lifecycleId));
                $scheduled++;
            }, 3);
        }

        $warningIds = AccountDeletionLifecycle::query()
            ->where('status', 'scheduled')
            ->whereNull('warning_sent_at')
            ->where(function ($query) use ($retryAfterMinutes): void {
                $query->where('warning_status', 'pending')
                    ->orWhere(function ($query) use ($retryAfterMinutes): void {
                        $query->where('warning_status', 'failed')
                            ->where(function ($query) use ($retryAfterMinutes): void {
                                $query->whereNull('warning_queued_at')
                                    ->orWhere('warning_queued_at', '<=', now()->subMinutes($retryAfterMinutes));
                            });
                    })
                    ->orWhere(function ($query) use ($retryAfterMinutes): void {
                        $query->whereIn('warning_status', ['queued', 'sending'])
                            ->where(function ($query) use ($retryAfterMinutes): void {
                                $query->whereNull('warning_queued_at')
                                    ->orWhere('warning_queued_at', '<=', now()->subMinutes($retryAfterMinutes));
                            });
                    });
            })
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        foreach ($warningIds as $id) {
            $queued = AccountDeletionLifecycle::query()->whereKey($id)->where('status', 'scheduled')
                ->whereNull('warning_sent_at')->update([
                    'warning_status' => 'queued',
                    'warning_queued_at' => now(),
                    'warning_error' => null,
                ]);
            if ($queued) {
                $this->dispatchWarning((int) $id);
            }
        }

        $dueIds = AccountDeletionLifecycle::query()
            ->where('status', 'scheduled')
            ->where('scheduled_deletion_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');
        $deleted = 0;
        $cancelled = 0;
        $deferred = 0;

        foreach ($dueIds as $id) {
            $outcome = $accounts->completeScheduledApplicantDeletion((int) $id);
            match ($outcome) {
                'deleted' => $deleted++,
                'cancelled' => $cancelled++,
                'notice_required' => $deferred++,
                default => null,
            };

            if ($outcome === 'notice_required') {
                AccountDeletionLifecycle::query()->whereKey($id)->where('status', 'scheduled')->update([
                    'scheduled_deletion_at' => now()->addDays($warningDays),
                ]);
                $lifecycle = AccountDeletionLifecycle::query()->find($id);
                if ($lifecycle && ! $lifecycle->warning_sent_at) {
                    $staleQueue = ! $lifecycle->warning_queued_at
                        || $lifecycle->warning_queued_at->lessThanOrEqualTo(now()->subMinutes($retryAfterMinutes));
                    if ($staleQueue) {
                        $lifecycle->forceFill([
                            'warning_status' => 'queued',
                            'warning_queued_at' => now(),
                        ])->save();
                        $this->dispatchWarning((int) $id);
                    }
                }
            }
        }

        $purged = $purges->processPendingFilePurges($limit);

        $this->info("Scheduled {$scheduled}; deleted {$deleted}; cancelled {$cancelled}; deferred for notice {$deferred}; file purges checked {$purged}.");

        return self::SUCCESS;
    }

    private function dispatchWarning(int $lifecycleId): void
    {
        try {
            SendApplicantDeletionWarning::dispatch($lifecycleId);
        } catch (Throwable $exception) {
            AccountDeletionLifecycle::query()->whereKey($lifecycleId)->where('status', 'scheduled')->update([
                'warning_status' => 'failed',
                'warning_error' => 'The warning email could not be queued; another attempt is pending.',
            ]);
            Log::warning('An applicant deletion warning could not be queued.', [
                'lifecycle_id' => $lifecycleId,
                'exception' => $exception::class,
            ]);
        }
    }
}
