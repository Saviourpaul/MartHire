<?php

namespace App\Services;

use App\Events\WorkspaceUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Publishes workspace invalidations after commit without failing the primary action. */
class WorkspaceUpdateBroadcaster
{
    /** @var list<string> */
    private const TOPICS = ['users', 'organizations', 'jobs', 'candidates', 'applications', 'team', 'interviews', 'notifications', 'demo-requests'];

    /** @param list<string> $topics */
    public function admins(array $topics, ?string $message = null, string $type = 'info'): void
    {
        $this->afterCommit('admins', $topics, $message, $type);
    }

    /** @param list<string> $topics */
    public function organization(int $organizationId, array $topics, ?string $message = null, string $type = 'info'): void
    {
        if ($organizationId < 1) {
            return;
        }

        $this->afterCommit('organizations.'.$organizationId, $topics, $message, $type);
    }

    /** @param list<string> $topics */
    public function applicant(int $userId, array $topics, ?string $message = null, string $type = 'info'): void
    {
        if ($userId < 1) {
            return;
        }

        $this->afterCommit('App.Models.User.'.$userId, $topics, $message, $type);
    }

    /** @param list<string> $topics */
    private function afterCommit(string $channel, array $topics, ?string $message, string $type): void
    {
        $topics = array_values(array_unique(array_intersect(self::TOPICS, $topics)));
        if ($topics === []) {
            return;
        }

        $type = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
        $dispatch = fn () => $this->dispatchSafely($channel, $topics, $message, $type);

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($dispatch);

            return;
        }

        $dispatch();
    }

    /** @param list<string> $topics */
    private function dispatchSafely(string $channel, array $topics, ?string $message, string $type): void
    {
        try {
            event(new WorkspaceUpdated(
                $channel,
                $topics,
                $message,
                $type,
            ));
        } catch (Throwable $exception) {
            Log::warning('Workspace update could not be broadcast; normal table refresh remains available.', [
                'channel' => $channel,
                'topics' => $topics,
                'exception' => $exception::class,
            ]);
        }
    }
}
