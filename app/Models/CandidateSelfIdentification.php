<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateSelfIdentification extends Model
{
    protected $fillable = ['gender_identity', 'disability_status', 'disability_details'];

    protected function casts(): array
    {
        return [
            'gender_identity' => 'encrypted',
            'ethnicity' => 'encrypted',
            'disability_status' => 'encrypted',
            'disability_details' => 'encrypted',
            'veteran_status' => 'encrypted',
            'declined_to_disclose' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ApplicantProfile::class, 'applicant_profile_id');
    }
}
