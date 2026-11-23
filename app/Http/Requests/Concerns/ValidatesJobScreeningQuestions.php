<?php

namespace App\Http\Requests\Concerns;

use App\Enums\JobScreeningQuestionType;
use App\Models\Job;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesJobScreeningQuestions
{
    protected function screeningQuestionRules(bool $allowExisting): array
    {
        $rules = [
            'screening_questions_submitted' => ['sometimes', 'accepted'],
            'screening_questions' => ['sometimes', 'array', 'max:15'],
        ];

        foreach ((array) $this->input('screening_questions', []) as $index => $question) {
            if (! is_array($question)) {
                continue;
            }

            $prefix = "screening_questions.{$index}";
            $type = JobScreeningQuestionType::tryFrom((string) ($question['type'] ?? ''));
            $rules["{$prefix}"] = ['array:id,prompt,type,required,options'];
            $rules["{$prefix}.id"] = $allowExisting ? ['nullable', 'integer', 'min:1'] : ['prohibited'];
            $rules["{$prefix}.prompt"] = ['required', 'string', 'min:5', 'max:500'];
            $rules["{$prefix}.type"] = ['required', Rule::in(array_map(static fn (JobScreeningQuestionType $type): string => $type->value, JobScreeningQuestionType::cases()))];
            $rules["{$prefix}.required"] = ['sometimes', 'boolean'];
            $rules["{$prefix}.options"] = $type?->hasOptions()
                ? ['required', 'array', 'min:2', 'max:10']
                : ['prohibited'];
            $rules["{$prefix}.options.*"] = ['required', 'string', 'min:1', 'max:100', 'distinct:ignore_case'];
        }

        return $rules;
    }

    protected function normalizeScreeningQuestionDefinitions(): void
    {
        $questions = $this->input('screening_questions');
        if (! is_array($questions)) {
            return;
        }

        foreach ($questions as $index => $question) {
            if (! is_array($question)) {
                continue;
            }
            if (is_string($question['prompt'] ?? null)) {
                $question['prompt'] = trim($question['prompt']);
            }
            if (is_array($question['options'] ?? null)) {
                $question['options'] = array_map(
                    fn ($option) => is_string($option) ? trim($option) : $option,
                    $question['options'],
                );
            }
            $questions[$index] = $question;
        }

        $this->merge(['screening_questions' => $questions]);
    }

    protected function validateScreeningQuestionOwnership(Validator $validator, bool $allowExisting): void
    {
        $questions = $this->input('screening_questions', []);
        if (! is_array($questions)) {
            return;
        }

        $ids = [];
        foreach ($questions as $index => $question) {
            if (! is_array($question) || blank($question['id'] ?? null)) {
                continue;
            }

            $id = filter_var($question['id'], FILTER_VALIDATE_INT);
            if ($id === false) {
                continue;
            }

            if (! $allowExisting || in_array($id, $ids, true)) {
                $validator->errors()->add("screening_questions.{$index}.id", 'This screening question cannot be used here.');
                continue;
            }

            $ids[] = $id;
        }

        if (! $allowExisting || $ids === []) {
            return;
        }

        $job = $this->route('job');
        if (! $job instanceof Job) {
            return;
        }

        $ownedIds = $job->screeningQuestions()->whereIn('id', $ids)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        foreach ($questions as $index => $question) {
            $id = is_array($question) ? filter_var($question['id'] ?? null, FILTER_VALIDATE_INT) : false;
            if ($id !== false && ! in_array($id, $ownedIds, true)) {
                $validator->errors()->add("screening_questions.{$index}.id", 'This screening question does not belong to this job.');
            }
        }
    }
}
