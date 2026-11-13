<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationOnboardingProgress extends Model
{
    protected $fillable = [
        'organization_member_id',
        'current_step',
        'recruitment_focus',
        'completed_steps',
        'skipped_steps',
        'completed_at',
        'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_steps' => 'array',
            'skipped_steps' => 'array',
            'completed_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'organization_member_id');
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }
}
