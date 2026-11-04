<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ApplicantProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'professional_headline',
        'about',
        'resume_path',
        'resume_original_name',
        'resume_mime_type',
        'resume_size',
    ];

    protected function casts(): array
    {
        return ['resume_size' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employments(): HasMany
    {
        return $this->hasMany(CandidateProfileEmployment::class)->latest('start_date')->latest('id');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(CandidateProfileEducation::class)->latest('end_date')->latest('id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(CandidateProfileSkill::class)->orderBy('name');
    }

    public function links(): HasMany
    {
        return $this->hasMany(CandidateProfileLink::class)->orderBy('platform');
    }

    public function selfIdentification(): HasOne
    {
        return $this->hasOne(CandidateSelfIdentification::class);
    }
}
