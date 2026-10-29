<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Scopes\OrganizationScope;
use Database\Factories\JobPostMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobPostMember extends Model
{
    /** @use HasFactory<JobPostMemberFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['organization_id', 'job_id', 'user_id'];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    protected function parentOrganizationId(): ?int
    {
        return Job::withoutGlobalScope(OrganizationScope::class)->whereKey($this->job_id)->value('organization_id');
    }
}
