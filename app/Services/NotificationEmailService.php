<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Mail\WorkflowAlertMail;
use App\Models\NotificationEmailOutbox;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationEmailService
{
    public function __construct(private NotificationPreferenceService $preferences, private NotificationInboxService $inbox) {}

    public function deliver(int $id): void
    {
        $claimed = DB::transaction(function () use ($id): bool {
            $entry = NotificationEmailOutbox::query()->lockForUpdate()->find($id);
            if (! $entry || ! in_array($entry->status, ['pending', 'processing'], true)
                || $entry->available_at->isFuture()
                || ($entry->status === 'processing' && $entry->locked_until?->isFuture())) {
                return false;
            }
            if ($entry->attempts >= 5) {
                $entry->update(['status' => 'failed', 'locked_until' => null]);

                return false;
            }
            $entry->update(['status' => 'processing', 'attempts' => $entry->attempts + 1, 'locked_until' => now()->addMinutes(2)]);

            return true;
        });
        if (! $claimed) {
            return;
        }
        $entry = NotificationEmailOutbox::query()->with('notification')->find($id);
        if (! $entry || $entry->status !== 'processing') {
            return;
        }

        DB::transaction(function () use ($id): void {
            $entry = NotificationEmailOutbox::query()->with('notification')->lockForUpdate()->findOrFail($id);
            if ($entry->status !== 'processing') {
                return;
            }
            $notification = $entry->notification;
            $user = $notification && $notification->notifiable_type === (new User)->getMorphClass()
                ? User::query()->find($notification->notifiable_id) : null;
            $category = NotificationCategory::tryFrom((string) $notification?->category);
            if (! $user || ! $category || ! $this->inbox->eligibleForDelivery($notification, $user)
                || ! $this->preferences->emailEnabled($user, $category, $notification->organization_id)) {
                $entry->update(['status' => 'cancelled', 'locked_until' => null, 'last_error' => null]);

                return;
            }
            try {
                Mail::to($user->email)->send(new WorkflowAlertMail(
                    (string) $notification->data['title'], (string) $notification->data['message'],
                    $this->inbox->actionUrl($notification, $user),
                ));
                $entry->update(['status' => 'sent', 'sent_at' => now(), 'locked_until' => null, 'last_error' => null]);
            } catch (Throwable) {
                $entry->update([
                    'status' => $entry->attempts >= 5 ? 'failed' : 'pending',
                    'available_at' => now()->addSeconds(min(3600, 60 * (2 ** $entry->attempts))),
                    'locked_until' => null, 'last_error' => 'Delivery failed. Automatic retries are bounded.',
                ]);
            }
        });
    }
}
