<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Services\EmailVerificationService;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'google_id',
        'email',
        'date_of_birth',
        'password',
        'role',
        'status',
        'approved_at',
        'suspended_at',
        'last_login_at',
        'profile_image_path',
        'phone',
        'phone_country',
        'phone_dial_code',
        'phone_national_number',
        'address',
        'nationality',
        'state_of_origin',
        'local_government_area',
        'country_code',
        'country',
        'state',
        'city',
        'location_confirmed_at',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'applicant',
        'status' => 'active',
        'authentication_version' => 0,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'authentication_version',
        'email_verification_code_hash',
        'email_verification_challenge_id',
        'email_verification_expires_at',
        'email_verification_requested_at',
        'email_verification_sent_at',
        'email_verification_failed_at',
        'email_verification_attempts',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'authentication_version' => 'integer',
            'email_verification_expires_at' => 'datetime',
            'email_verification_requested_at' => 'datetime',
            'email_verification_sent_at' => 'datetime',
            'email_verification_failed_at' => 'datetime',
            'email_verification_attempts' => 'integer',
            'welcome_email_queued_at' => 'datetime',
            'welcome_email_sent_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
            'location_confirmed_at' => 'datetime',
        ];
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'employer_id');
    }

    public function userPreference(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function jobPreferences(): HasOne
    {
        return $this->hasOne(CandidateJobPreference::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_members')
            ->withPivot(['role', 'status', 'deleted_at'])
            ->wherePivot('status', 'active')
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    public function jobAssignments(): HasMany
    {
        return $this->hasMany(JobPostMember::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ApplicationForm::class);
    }

    public function identificationDocument(): HasOne
    {
        return $this->hasOne(UserIdentificationDocument::class);
    }

    public function profileImageUrl(): string
    {
        if (! $this->profile_image_path) {
            return asset('admin/assets/images/Avatar.png');
        }

        $path = ltrim($this->profile_image_path, '/');

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        if (! Storage::disk('public')->exists($path)) {
            return asset('admin/assets/images/Avatar.png');
        }

        return asset('storage/'.$path);
    }

    /**
     * @return array<string, string>
     */
    public static function applicantProfileFields(): array
    {
        return [
            'profile_image_path' => 'Profile image',
            'date_of_birth' => 'Date of birth',
            'phone' => 'Phone number',
            'address' => 'Address',
            'location_confirmed_at' => 'Location',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function missingApplicantProfileFields(): array
    {
        if (! $this->isApplicant()) {
            return [];
        }

        return collect(self::applicantProfileFields())
            ->filter(fn (string $label, string $field): bool => blank($this->{$field}))
            ->all();
    }

    public function applicantProfileCompletionPercentage(): int
    {
        if (! $this->isApplicant()) {
            return 100;
        }

        $total = count(self::applicantProfileFields());
        $missing = count($this->missingApplicantProfileFields());

        return (int) round((($total - $missing) / $total) * 100);
    }

    public function hasCompletedApplicantProfile(): bool
    {
        return $this->isApplicant() && $this->missingApplicantProfileFields() === [];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isOrganization(): bool
    {
        return $this->role === UserRole::Organization;
    }

    public function isApplicant(): bool
    {
        return $this->role === UserRole::Applicant;
    }

    public function hasRole(UserRole|string ...$roles): bool
    {
        $values = array_map(
            fn (UserRole|string $role): string => $role instanceof UserRole ? $role->value : $role,
            $roles
        );

        return in_array($this->role->value, $values, true);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    public function scopeRole(Builder $query, UserRole|string $role): Builder
    {
        return $query->where('role', $role instanceof UserRole ? $role->value : $role);
    }

    public function scopeStatus(Builder $query, UserStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof UserStatus ? $status->value : $status);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->status(UserStatus::Active);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->status(UserStatus::Suspended);
    }

    public function activate(): bool
    {
        $this->status = UserStatus::Active;
        $this->approved_at ??= now();
        $this->suspended_at = null;

        return $this->save();
    }

    /**
     * Issue a time-limited code and queue its encrypted email delivery.
     */
    public function sendEmailVerificationNotification(): void
    {
        app(EmailVerificationService::class)->sendCode($this);
    }

    public function suspend(): bool
    {
        $this->status = UserStatus::Suspended;
        $this->suspended_at = now();

        return $this->save();
    }
}
