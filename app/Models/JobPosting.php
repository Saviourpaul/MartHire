<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobPosting extends Model
{
    protected $fillable = [
        'company_id',
        'shortcode',
        'external_id',
        'title',
        'full_title',
        'state',
        'department',
        'workplace_type',
        'url',
        'application_url',
        'shortlink',
        'location',
        'locations',
        'salary',
        'keywords',
        'content_html',
        'content_hash',
        'external_updated_at',
        'status',
        'last_seen_at',
        'closed_at',
        'apply_supported',
    ];

    protected $casts = [
        'location' => 'array',
        'locations' => 'array',
        'salary' => 'array',
        'keywords' => 'array',
        'external_updated_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'closed_at' => 'datetime',
        'apply_supported' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
