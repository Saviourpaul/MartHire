<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AccountDeletionLifecycle extends Model
{
    protected $fillable = [
        'reference',
        'user_id',
        'active_user_id',
        'initiated_by_user_id',
        'role_snapshot',
        'reason',
        'status',
        'last_login_at_snapshot',
        'scheduled_deletion_at',
        'warning_status',
        'warning_queued_at',
        'warning_sent_at',
        'warning_attempts',
        'warning_error',
        'cancelled_at',
        'completed_at',
        'purge_status',
        'purge_attempts',
        'purge_error',
        'purge_paths',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at_snapshot' => 'datetime',
            'scheduled_deletion_at' => 'datetime',
            'warning_queued_at' => 'datetime',
            'warning_sent_at' => 'datetime',
            'warning_attempts' => 'integer',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'purge_attempts' => 'integer',
            'purge_paths' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $lifecycle): void {
            $lifecycle->reference ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function activeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'active_user_id')->withTrashed();
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id')->withTrashed();
    }
}
