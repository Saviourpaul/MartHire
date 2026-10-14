<?php

namespace App\Models;

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\OrganizationMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationMember extends Model
{
    /** @use HasFactory<OrganizationMemberFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = ['organization_id', 'user_id', 'role', 'status'];

    protected $attributes = ['role' => 'recruiter', 'status' => 'active'];

    protected function casts(): array
    {
        return ['role' => OrganizationRole::class, 'status' => OrganizationMemberStatus::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function isActive(): bool
    {
        return $this->status === OrganizationMemberStatus::Active && ! $this->trashed();
    }
}
