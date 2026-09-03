<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\ApplicationFormFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationForm extends Model
{
    /** @use HasFactory<ApplicationFormFactory> */
    use HasFactory;

    protected $fillable = [
        'job_id',
        'user_id',
        'reference',
        'status',
        'submitted_at',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'nationality',
        'date_of_birth',
        'gender',
        'marital_status',
        'state_of_origin',
        'local_government_area',
        'address',
        'zipcode',
        'profile_image_path',
        'reviewed_by',
        'reviewed_at',
        'employer_remarks',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'submitted_at' => 'datetime',
            'date_of_birth' => 'date',
            'reviewed_at' => 'datetime',
            
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class);
    }

    public function scopeForEmployer(Builder $query, User $employer): Builder
    {
        return $query->whereHas('job', fn (Builder $jobQuery) => $jobQuery->where('employer_id', $employer->id));
    }

    public function scopeStatus(Builder $query, ApplicationStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof ApplicationStatus ? $status->value : $status);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        $term = '%'.trim($search).'%';

        return $query->where(function (Builder $query) use ($term) {
            $query->where('reference', 'like', $term)
                ->orWhere('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhereHas('job', function (Builder $jobQuery) use ($term) {
                    $jobQuery->where('title', 'like', $term)
                        ->orWhere('company', 'like', $term);
                });
        });
    }
}
