<?php

namespace App\Models;

use App\Enums\JobStatus;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasFactory;

    protected $table = 'job_posts';

    protected $fillable = [
        'employer_id',
        'title',
        'description',
        'company',
        'category',
        'location',
        'employment_type',
        'logo',
        'start_date',
        'due_date',
        'status',
    ];
    public const EMPLOYMENT_TYPES = [
        'full_time' => 'Full-time',
        'part_time' => 'Part-time',
        'contract' => 'Contract',
        'internship' => 'Internship',
        'freelance' => 'Freelance',
    ];


    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'status' => JobStatus::class,
        ];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ApplicationForm::class);
    }

    /**
     * @return array<string, string>
     */
    public static function employmentTypeOptions(): array
    {
        return self::EMPLOYMENT_TYPES;
    }

    public function scopeStatus(Builder $query, JobStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof JobStatus ? $status->value : $status);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->status(JobStatus::Approved);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->approved();
    }

    public function scopeAcceptingApplications(Builder $query): Builder
    {
        return $query
            ->approved()
            ->whereDate('due_date', '>=', now()->toDateString());
    }

    public function isApproved(): bool
    {
        return $this->status === JobStatus::Approved;
    }

    public function isAcceptingApplications(): bool
    {
        return $this->isApproved() && ! $this->due_date?->isBefore(today());
    }

    public function isPending(): bool
    {
        return $this->status === JobStatus::Pending;
    }

    public function isRejected(): bool
    {
        return $this->status === JobStatus::Rejected;
    }

    public function employmentTypeLabel(): string
    {
        return self::EMPLOYMENT_TYPES[$this->employment_type] ?? 'Not specified';
    }

    public function logoUrl(): string
    {
        $defaultLogo = 'assets/images/brands/company-logo-1.png';

        if (! $this->logo) {
            return asset($defaultLogo);
        }

        $path = ltrim($this->logo, '/');

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        if (Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return asset($defaultLogo);
    }

    public function storedLogoPath(): ?string
    {
        if (! $this->logo || filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return null;
        }

        $path = ltrim($this->logo, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        return Storage::disk('public')->exists($path) ? $path : null;
    }
}







