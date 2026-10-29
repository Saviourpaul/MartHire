<?php

namespace App\Models;

use App\Enums\JobAlertFrequency;
use Database\Factories\CandidateJobPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateJobPreference extends Model
{
    /** @use HasFactory<CandidateJobPreferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_alert_frequency',
        'profile_visible_to_employers',
        'preferred_roles',
        'preferred_locations',
        'preferred_job_type',
        'salary_min',
        'salary_max',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'job_alert_frequency' => JobAlertFrequency::class,
            'profile_visible_to_employers' => 'boolean',
            'preferred_roles' => 'array',
            'preferred_locations' => 'array',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'version' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
