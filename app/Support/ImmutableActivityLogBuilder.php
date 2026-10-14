<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/** @extends Builder<ActivityLog> */
class ImmutableActivityLogBuilder extends Builder
{
    public function update(array $values): never
    {
        throw new LogicException('Activity logs cannot be changed.');
    }

    public function delete(): never
    {
        throw new LogicException('Activity logs cannot be deleted.');
    }

    public function forceDelete(): never
    {
        throw new LogicException('Activity logs cannot be deleted.');
    }

    public function increment(mixed $column, mixed $amount = 1, array $extra = []): never
    {
        throw new LogicException('Activity logs cannot be changed.');
    }

    public function decrement(mixed $column, mixed $amount = 1, array $extra = []): never
    {
        throw new LogicException('Activity logs cannot be changed.');
    }

    public function incrementEach(array $columns, array $extra = []): never
    {
        throw new LogicException('Activity logs cannot be changed.');
    }

    public function decrementEach(array $columns, array $extra = []): never
    {
        throw new LogicException('Activity logs cannot be changed.');
    }

    public function upsert(array $values, mixed $uniqueBy, mixed $update = null): never
    {
        throw new LogicException('Activity logs cannot be changed.');
    }
}
