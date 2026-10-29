<?php

namespace App\Models;

use App\Enums\CandidatePipelineStage;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'application_form_id',
        'from_status',
        'to_status',
        'changed_by',
        'remarks',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => CandidatePipelineStage::class,
            'to_status' => CandidatePipelineStage::class,
            'created_at' => 'datetime',
        ];
    }

    public function applicationForm(): BelongsTo
    {
        return $this->belongsTo(ApplicationForm::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by')->withTrashed();
    }

    protected function parentOrganizationId(): ?int
    {
        return ApplicationForm::withoutGlobalScope(OrganizationScope::class)
            ->whereKey($this->application_form_id)->value('organization_id');
    }
}
