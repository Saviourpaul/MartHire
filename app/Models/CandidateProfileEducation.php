<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProfileEducation extends Model
{
    protected $table = 'candidate_profile_educations';

    protected $fillable = ['institution', 'qualification', 'field_of_study', 'start_date', 'end_date', 'description'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ApplicantProfile::class, 'applicant_profile_id');
    }
}
