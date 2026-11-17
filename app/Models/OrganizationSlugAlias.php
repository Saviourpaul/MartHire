<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSlugAlias extends Model
{
    public $timestamps = false;

    protected $fillable = ['organization_id', 'slug'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
