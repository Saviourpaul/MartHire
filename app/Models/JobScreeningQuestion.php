<?php

namespace App\Models;

use App\Enums\JobScreeningQuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class JobScreeningQuestion extends Model
{
    protected $fillable = [
        'job_id',
        'question_key',
        'type',
        'prompt',
        'options',
        'is_required',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'type' => JobScreeningQuestionType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (JobScreeningQuestion $question): void {
            $question->question_key ??= (string) Str::uuid();
        });
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
