<?php

namespace App\Support;

use App\Models\CandidateMessage;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/** @extends Builder<CandidateMessage> */
class CandidateMessageBuilder extends Builder
{
    public function update(array $values): int
    {
        if (array_diff(array_keys($values), CandidateMessage::DELIVERY_FIELDS)) {
            throw new LogicException('Candidate email snapshots cannot be changed.');
        }

        return parent::update($values);
    }

    public function delete(): never
    {
        throw new LogicException('Candidate email history cannot be deleted.');
    }

    public function forceDelete(): never
    {
        throw new LogicException('Candidate email history cannot be deleted.');
    }

    public function truncate(): never
    {
        throw new LogicException('Candidate email history cannot be truncated.');
    }

    public function updateOrInsert(array $attributes, array|callable $values = []): never
    {
        throw new LogicException('Candidate email snapshots cannot be changed.');
    }

    public function upsert(array $values, mixed $uniqueBy, mixed $update = null): never
    {
        throw new LogicException('Candidate email snapshots cannot be upserted.');
    }

    public function increment(mixed $column, mixed $amount = 1, array $extra = []): never
    {
        throw new LogicException('Candidate messages must be updated through delivery transitions.');
    }

    public function decrement(mixed $column, mixed $amount = 1, array $extra = []): never
    {
        throw new LogicException('Candidate messages must be updated through delivery transitions.');
    }

    public function incrementEach(array $columns, array $extra = []): never
    {
        throw new LogicException('Candidate messages must be updated through delivery transitions.');
    }

    public function decrementEach(array $columns, array $extra = []): never
    {
        throw new LogicException('Candidate messages must be updated through delivery transitions.');
    }
}
