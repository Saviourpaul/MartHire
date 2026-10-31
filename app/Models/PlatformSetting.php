<?php

namespace App\Models;

use Database\Factories\PlatformSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSetting extends Model
{
    /** @use HasFactory<PlatformSettingFactory> */
    use HasFactory;

    protected $fillable = ['display_name', 'mail_from_name', 'mail_from_address', 'support_email', 'support_phone', 'support_address', 'support_url', 'privacy_url', 'terms_url', 'site_logo_path', 'logo_light_path', 'logo_dark_path', 'icon_path', 'facebook_url', 'x_url', 'instagram_url', 'linkedin_url', 'youtube_url', 'tiktok_url', 'public_registration_enabled', 'version', 'updated_by'];

    protected $attributes = ['display_name' => 'Marthire', 'public_registration_enabled' => true, 'version' => 1];

    protected function casts(): array
    {
        return ['public_registration_enabled' => 'boolean', 'version' => 'integer'];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
