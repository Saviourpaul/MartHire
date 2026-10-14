<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Scopes\OrganizationScope;
use App\Support\ImmutableActivityLogBuilder;
use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use BelongsToOrganization, HasFactory;

    public $timestamps = false;

    protected $fillable = ['organization_id', 'application_form_id', 'actor_id', 'type', 'payload', 'created_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'created_at' => 'datetime'];
    }

    public function newEloquentBuilder(mixed $query): ImmutableActivityLogBuilder
    {
        return new ImmutableActivityLogBuilder($query);
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Activity logs cannot be changed.');
        });
        static::deleting(function (): never {
            throw new LogicException('Activity logs cannot be deleted.');
        });
    }

    public function applicationForm(): BelongsTo
    {
        return $this->belongsTo(ApplicationForm::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    protected function parentOrganizationId(): ?int
    {
        if ($this->application_form_id === null) {
            return null;
        }

        return ApplicationForm::withoutGlobalScope(OrganizationScope::class)
            ->whereKey($this->application_form_id)->value('organization_id');
    }
}
