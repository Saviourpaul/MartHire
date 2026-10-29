<?php

namespace App\Console\Commands;

use App\Models\NotificationEmailOutbox;
use App\Services\WorkflowNotificationService;
use Illuminate\Console\Command;

class DrainNotificationOutbox extends Command
{
    protected $signature = 'notifications:drain-outbox {--limit=200 : Maximum pending entries to dispatch}';

    protected $description = 'Dispatch due workflow notification emails from the durable outbox';

    public function handle(): int
    {
        $ids = NotificationEmailOutbox::query()->where('available_at', '<=', now())
            ->where(function ($query): void {
                $query->where('status', 'pending')->orWhere(function ($expired): void {
                    $expired->where('status', 'processing')->where('locked_until', '<=', now());
                });
            })->orderBy('id')->limit(max(1, min(1000, (int) $this->option('limit'))))->pluck('id');
        foreach ($ids as $id) {
            WorkflowNotificationService::wake((int) $id);
        }
        $this->info($ids->count().' pending notification deliveries dispatched.');

        return self::SUCCESS;
    }
}
