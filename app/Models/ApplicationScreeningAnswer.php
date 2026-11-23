<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationScreeningAnswer extends Model
{
    protected $fillable = [
        'application_form_id',
        'question_key',
        'type',
        'prompt',
        'options',
        'is_required',
        'position',
        'answer',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected function answer(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): mixed => $value === null ? null : json_decode($value, true),
            set: fn (mixed $value): ?string => $value === null ? null : json_encode($value, JSON_THROW_ON_ERROR),
        );
    }

    public function applicationForm(): BelongsTo
    {
        return $this->belongsTo(ApplicationForm::class);
    }
}
