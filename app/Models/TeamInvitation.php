<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use App\Enums\TeamInvitationStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TeamInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamInvitation extends Model
{
    /** @use HasFactory<TeamInvitationFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id', 'email', 'role', 'token_hash', 'expires_at', 'status',
        'invited_by', 'accepted_at', 'declined_at',
    ];

    protected $attributes = ['status' => 'pending'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
            'status' => TeamInvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by')->withTrashed();
    }

    public function isPending(): bool
    {
        return $this->status === TeamInvitationStatus::Pending && $this->expires_at?->isFuture();
    }
}
