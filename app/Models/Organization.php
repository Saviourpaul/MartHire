<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    protected static function booted(): void
    {
        static::creating(function (Organization $organization): void {
            if (blank($organization->slug)) {
                $base = mb_substr(Str::slug($organization->name) ?: 'organization', 0, 220);
                $slug = $base;
                $suffix = 1;
                while (static::query()->where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$suffix++;
                }
                $organization->slug = $slug;
            }
        });
    }

    public function settings(): HasOne
    {
        return $this->hasOne(OrganizationSetting::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
