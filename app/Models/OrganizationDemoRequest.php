<?php

namespace App\Models;

use App\Enums\OrganizationDemoOnboardingStatus;
use App\Enums\OrganizationDemoRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationDemoRequest extends Model
{
    protected $fillable = [
        'contact_first_name',
        'contact_last_name',
        'company_name',
        'business_email',
        'active_email_hash',
        'email_domain_hash',
        'risk_flags',
        'possible_duplicate_of_id',
        'phone',
        'phone_country',
        'phone_dial_code',
        'phone_national_number',
        'industry',
        'organization_size',
        'status',
        'onboarding_status',
        'assigned_admin_id',
        'reviewed_by_id',
        'demo_scheduled_at',
        'demo_timezone',
        'internal_notes',
        'rejection_reason',
        'setup_token_hash',
        'setup_signed_at',
        'setup_expires_at',
        'setup_sent_at',
        'setup_accepted_at',
        'requester_update_token_hash',
        'requester_update_expires_at',
        'requester_information_response',
        'requester_information_received_at',
        'reviewed_at',
        'approved_at',
        'rejected_at',
        'completed_at',
        'converted_user_id',
        'converted_organization_id',
    ];

    protected $hidden = [
        'active_email_hash',
        'email_domain_hash',
        'risk_flags',
        'possible_duplicate_of_id',
        'setup_token_hash',
        'requester_update_token_hash',
        'requester_information_response',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrganizationDemoRequestStatus::class,
            'onboarding_status' => OrganizationDemoOnboardingStatus::class,
            'risk_flags' => 'array',
            'demo_scheduled_at' => 'datetime',
            'setup_expires_at' => 'datetime',
            'setup_sent_at' => 'datetime',
            'setup_accepted_at' => 'datetime',
            'setup_signed_at' => 'datetime',
            'requester_update_expires_at' => 'datetime',
            'requester_information_received_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotNull('active_email_hash');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id')->withTrashed();
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id')->withTrashed();
    }

    public function convertedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_user_id')->withTrashed();
    }

    public function convertedOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'converted_organization_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrganizationDemoRequestEvent::class)->latest('id');
    }

    public function possibleDuplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'possible_duplicate_of_id');
    }
}
