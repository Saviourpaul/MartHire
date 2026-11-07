<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A minimal invalidation event; authorized HTTP fragments remain authoritative. */
class WorkspaceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @param list<string> $topics */
    public function __construct(
        private readonly string $channel,
        private readonly array $topics,
        private readonly ?string $message = null,
        private readonly string $type = 'info',
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->channel)];
    }

    public function broadcastAs(): string
    {
        return 'workspace.updated';
    }

    /** @return array{topics: list<string>, message: ?string, type: string} */
    public function broadcastWith(): array
    {
        return [
            'topics' => $this->topics,
            'message' => $this->message,
            'type' => $this->type,
        ];
    }
}
