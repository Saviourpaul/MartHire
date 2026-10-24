<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_posting_id',
        'idempotency_key',
        'email',
        'first_name',
        'last_name',
        'status',
        'attempts',
        'error_code',
        'payload',
        'schema_hash',
        'workable_candidate_id',
        'http_status',
        'response_excerpt',
        'consent_version',
        'consented_at',
        'submitted_at',
    ];

    /** The payload holds candidate answers + file references until purged — encrypted at rest. */
    protected $hidden = ['payload'];

    protected $casts = [
        'payload' => 'encrypted:array',
        'consented_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }
}
