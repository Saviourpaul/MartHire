<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProfileEmployment extends Model
{
    protected $fillable = [
        'company', 'title', 'location', 'employment_type', 'start_date', 'end_date', 'is_current', 'description',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'is_current' => 'boolean'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ApplicantProfile::class, 'applicant_profile_id');
    }
}
