<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NigeriaState extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'sort_order',
    ];

    public function localGovernmentAreas(): HasMany
    {
        return $this->hasMany(NigeriaLocalGovernmentArea::class)->orderBy('sort_order')->orderBy('name');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
