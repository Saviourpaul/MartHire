<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Jobs\SendVerificationEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'address',
        'nationality',
        'state_of_origin',
        'local_government_area',
        'country_code',
        'country',
        'state',
        'city',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'applicant',
        'status' => 'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'welcome_email_queued_at' => 'datetime',
            'welcome_email_sent_at' => 'datetime',
            'date_of_birth' => 'date',
            'location_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'employer_id');
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
            'location_confirmed_at' => 'Country, state and city',
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

        $fields = self::applicantProfileFields();
        // Grandfather completed legacy locations only until the next successful edit/application.
        $legacyComplete = filled($this->nationality) && filled($this->state_of_origin) && filled($this->local_government_area);

        return collect($fields)->filter(function (string $label, string $field) use ($legacyComplete): bool {
            if ($field === 'location_confirmed_at') {
                return $this->location_confirmed_at
                    ? blank($this->country_code) || blank($this->country)
                    : ! $legacyComplete;
            }

            return blank($this->{$field});
        })->all();
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

    public function isEmployer(): bool
    {
        return $this->role === UserRole::Employer;
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
     * Queue Laravel's standard signed verification notification instead of
     * performing SMTP work during registration or a resend HTTP request.
     */
    public function sendEmailVerificationNotification(): void
    {
        SendVerificationEmail::dispatch((int) $this->getKey())->afterCommit();
    }

    public function suspend(): bool
    {
        $this->status = UserStatus::Suspended;
        $this->suspended_at = now();

        return $this->save();
    }
}
