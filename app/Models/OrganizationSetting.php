<?php

namespace App\Models;

use Database\Factories\OrganizationSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSetting extends Model
{
    /** @use HasFactory<OrganizationSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'company_logo_path',
        'about',
        'careers_url',
        'public_contact_email',
        'public_contact_phone',
        'default_interview_timezone',
        'default_interview_contact_name',
        'default_interview_contact_phone',
        'default_employment_type',
        'default_application_instructions',
        'version',
    ];

    protected $attributes = ['version' => 1];

    protected function casts(): array
    {
        return ['version' => 'integer'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
