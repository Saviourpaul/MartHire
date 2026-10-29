<?php

namespace App\Models;

use App\Enums\InterviewCandidateResponse;
use App\Enums\InterviewMode;
use App\Enums\InterviewPlatform;
use App\Enums\InterviewStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Scopes\OrganizationScope;
use Database\Factories\InterviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Interview extends Model
{
    /** @use HasFactory<InterviewFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id', 'application_form_id', 'mode', 'platform', 'meeting_link',
        'venue_name', 'address', 'what_to_bring', 'directions', 'instructions',
        'contact_name', 'contact_phone', 'start_at', 'end_at', 'timezone', 'status',
        'candidate_response', 'responded_at', 'version',
    ];

    protected $attributes = ['status' => 'scheduled', 'candidate_response' => 'pending', 'version' => 1];

    protected function casts(): array
    {
        return [
            'mode' => InterviewMode::class,
            'platform' => InterviewPlatform::class,
            'status' => InterviewStatus::class,
            'candidate_response' => InterviewCandidateResponse::class,
            'start_at' => 'immutable_datetime',
            'end_at' => 'immutable_datetime',
            'responded_at' => 'immutable_datetime',
            'version' => 'integer',
        ];
    }

    public function applicationForm(): BelongsTo
    {
        return $this->belongsTo(ApplicationForm::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CandidateMessage::class);
    }

    protected function parentOrganizationId(): ?int
    {
        return ApplicationForm::withoutGlobalScope(OrganizationScope::class)
            ->whereKey($this->application_form_id)->value('organization_id');
    }
}
