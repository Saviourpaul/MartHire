<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationDemoRequestEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['organization_demo_request_id', 'actor_id', 'event', 'details', 'created_at'];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(OrganizationDemoRequest::class, 'organization_demo_request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
