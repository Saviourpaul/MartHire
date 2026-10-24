<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'subdomain',
        'api_token',
        'scopes',
        'apply_mode',
        'credentials_status',
        'last_synced_at',
    ];

    /** The API token is a secret: never serialized. */
    protected $hidden = ['api_token'];

    protected $casts = [
        'api_token' => 'encrypted',
        'scopes' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
