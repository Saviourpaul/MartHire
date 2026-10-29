<?php

namespace App\Models;

use Database\Factories\NotificationEmailOutboxFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationEmailOutbox extends Model
{
    /** @use HasFactory<NotificationEmailOutboxFactory> */
    use HasFactory;

    protected $table = 'notification_email_outbox';

    protected $fillable = ['notification_id', 'channel', 'status', 'attempts', 'available_at', 'locked_until', 'sent_at', 'last_error'];

    protected $attributes = ['channel' => 'mail', 'status' => 'pending', 'attempts' => 0];

    protected function casts(): array
    {
        return ['attempts' => 'integer', 'available_at' => 'datetime', 'locked_until' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(WorkflowNotification::class, 'notification_id');
    }
}
