<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NigeriaLocalGovernmentArea extends Model
{
    protected $fillable = [
        'nigeria_state_id',
        'name',
        'slug',
        'sort_order',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(NigeriaState::class, 'nigeria_state_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
