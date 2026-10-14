<?php

namespace App\Models;

use App\Enums\JobStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Scopes\OrganizationScope;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use BelongsToOrganization, HasFactory;

    protected $table = 'job_posts';

    protected $fillable = [
        'organization_id',
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
        'status' => 'open',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'status' => JobStatus::class,
            'suspended_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Job $job) => $job->slug ??= static::generateUniqueSlug($job->title));
    }

    protected static function generateUniqueSlug(string $title): string
    {
        $slug = $base = Str::slug($title);
        $i = 1;

        while (static::withoutGlobalScope(OrganizationScope::class)->where('slug', $slug)->exists()) {
            $slug = "{$base}-".$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id')->withTrashed();
    }

    public function members(): HasMany
    {
        return $this->hasMany(JobPostMember::class);
    }

    public function teamMembers(): HasManyThrough
    {
        return $this->hasManyThrough(OrganizationMember::class, JobPostMember::class, 'job_id', 'user_id', 'id', 'user_id')
            ->where('organization_members.organization_id', $this->organization_id)
            ->with('user');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ApplicationForm::class);
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
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

    public function scopeOpen(Builder $query): Builder
    {
        return $query->status(JobStatus::Open);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->open();
    }

    public function scopeAcceptingApplications(Builder $query): Builder
    {
        return $query
            ->active()
            ->where('due_date', '>=', today()->toDateString());
    }

    public function isOpen(): bool
    {
        return $this->status === JobStatus::Open;
    }

    public function isAcceptingApplications(): bool
    {
        return $this->isOpen() && ! $this->due_date?->isBefore(today());
    }

    public function isSuspended(): bool
    {
        return $this->status === JobStatus::Suspended;
    }

    public function isClosed(): bool
    {
        return $this->status === JobStatus::Closed;
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
