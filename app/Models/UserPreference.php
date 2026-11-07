<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = ['user_id', 'theme', 'timezone', 'notification_sound_enabled'];

    protected $attributes = ['theme' => 'system'];

    protected function casts(): array
    {
        return ['notification_sound_enabled' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
