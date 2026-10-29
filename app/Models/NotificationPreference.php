<?php

namespace App\Models;

use App\Enums\NotificationCategory;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    /** @use HasFactory<NotificationPreferenceFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'category', 'email_enabled'];

    protected $attributes = ['email_enabled' => false];

    protected function casts(): array
    {
        return ['category' => NotificationCategory::class, 'email_enabled' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
