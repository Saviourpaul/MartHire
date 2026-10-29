<?php

namespace App\Models;

use App\Enums\SecurityActionPurpose;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityChallenge extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'user_id', 'purpose', 'session_hash', 'email_hash', 'authentication_version', 'code_hash', 'ip_hash', 'attempts', 'expires_at', 'sent_at', 'failed_at', 'consumed_at'];

    protected $hidden = ['code_hash', 'session_hash', 'email_hash', 'ip_hash'];

    protected $attributes = ['attempts' => 0];

    protected function casts(): array
    {
        return ['purpose' => SecurityActionPurpose::class, 'authentication_version' => 'integer', 'attempts' => 'integer', 'expires_at' => 'datetime', 'sent_at' => 'datetime', 'failed_at' => 'datetime', 'consumed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
