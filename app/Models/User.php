<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
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
        'zipcode',
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
            'date_of_birth' => 'date',
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

    public function profileImageUrl(): string
    {
        if (! $this->profile_image_path) {
            return asset('admin/assets/img/Avatar.png');
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
            return asset('admin/assets/img/Avatar.png');
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
            'nationality' => 'Nationality',
            'state_of_origin' => 'State of origin',
            'local_government_area' => 'Local government area',
            'zipcode' => 'Zipcode',
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

    public function suspend(): bool
    {
        $this->status = UserStatus::Suspended;
        $this->suspended_at = now();

        return $this->save();
    }
}
