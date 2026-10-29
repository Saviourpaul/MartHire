<?php

namespace App\Models;

use Database\Factories\OrganizationMemberPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationMemberPreference extends Model
{
    /** @use HasFactory<OrganizationMemberPreferenceFactory> */
    use HasFactory;

    protected $fillable = ['organization_member_id', 'new_application_email', 'interview_response_email', 'candidate_email_failure_email'];

    protected function casts(): array
    {
        return ['new_application_email' => 'boolean', 'interview_response_email' => 'boolean', 'candidate_email_failure_email' => 'boolean'];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMember::class, 'organization_member_id');
    }
}
