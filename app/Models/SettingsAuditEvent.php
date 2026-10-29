<?php

namespace App\Models;

use App\Support\ImmutableSettingsAuditBuilder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class SettingsAuditEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_id', 'user_id', 'scope', 'action', 'changes', 'created_at'];

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'immutable_datetime'];
    }

    public function newEloquentBuilder(mixed $query): ImmutableSettingsAuditBuilder
    {
        return new ImmutableSettingsAuditBuilder($query);
    }

    protected static function booted(): void
    {
        static::creating(function (SettingsAuditEvent $event): void {
            $event->created_at ??= now();
        });
        static::updating(function (): never {
            throw new LogicException('Settings audit events cannot be changed.');
        });
        static::deleting(function (): never {
            throw new LogicException('Settings audit events cannot be deleted.');
        });
    }
}
