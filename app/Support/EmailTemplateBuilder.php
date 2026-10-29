<?php

namespace App\Support;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/** @extends Builder<EmailTemplate> */
class EmailTemplateBuilder extends Builder
{
    /** Columns that may be changed on a system template (content only). */
    private const CONTENT_COLUMNS = ['subject', 'body', 'name', 'is_active', 'updated_by', 'updated_at'];

    public function update(array $values): int
    {
        if (array_intersect(array_keys($values), ['organization_id', 'type', 'is_system', 'system_key'])) {
            throw new LogicException('Template ownership cannot be changed.');
        }

        // System templates permit content-only edits; any other column change is blocked.
        if (array_diff(array_keys($values), self::CONTENT_COLUMNS)) {
            $this->protectSystemTemplates();
        }

        return parent::update($values);
    }

    public function delete(): mixed
    {
        $this->protectSystemTemplates();

        return parent::delete();
    }

    public function forceDelete(): never
    {
        throw new LogicException('Email templates cannot be force deleted.');
    }

    public function truncate(): never
    {
        throw new LogicException('Email templates cannot be truncated.');
    }

    public function updateOrInsert(array $attributes, array|callable $values = []): never
    {
        throw new LogicException('Email templates must be saved individually.');
    }

    public function upsert(array $values, mixed $uniqueBy, mixed $update = null): never
    {
        throw new LogicException('Email templates must be saved individually.');
    }

    public function increment(mixed $column, mixed $amount = 1, array $extra = []): never
    {
        throw new LogicException('Email templates cannot be incremented.');
    }

    public function decrement(mixed $column, mixed $amount = 1, array $extra = []): never
    {
        throw new LogicException('Email templates cannot be decremented.');
    }

    public function incrementEach(array $columns, array $extra = []): never
    {
        throw new LogicException('Email templates cannot be incremented.');
    }

    public function decrementEach(array $columns, array $extra = []): never
    {
        throw new LogicException('Email templates cannot be decremented.');
    }

    private function protectSystemTemplates(): void
    {
        if ((clone $this)->where('is_system', true)->exists()) {
            throw new LogicException('System email templates cannot be changed or deleted.');
        }
    }
}
