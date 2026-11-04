<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProfileSkill extends Model
{
    protected $fillable = ['name'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ApplicantProfile::class, 'applicant_profile_id');
    }
}
