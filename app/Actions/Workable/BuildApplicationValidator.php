<?php

namespace App\Actions\Workable;

use App\Integrations\Workable\DTO\ApplicationSchema;
use App\Integrations\Workable\DTO\QuestionData;

/**
 * Turns a cached application schema into Laravel validation rules (design §7.3).
 * We are authoritative: Workable validates too, but we validate server-side for
 * UX. We always collect first_name/last_name/email/resume; everything else is
 * derived from the schema. Custom questions are keyed `question_{id}`.
 */
class BuildApplicationValidator
{
    private const RESUME_MIMES = 'pdf,doc,docx,rtf,txt';

    /** @return array<string, array<int, string>> */
    public function rules(ApplicationSchema $schema): array
    {
        $maxKb = (int) config('workable.application.max_file_mb', 5) * 1024;

        // Always-collected candidate basics.
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'cover_letter' => ['nullable', 'string'],
        ];

        // Resume: required unless the schema's resume field is explicitly optional.
        $resumeRequired = true;
        foreach ($schema->formFields as $field) {
            if (($field['key'] ?? null) === 'resume') {
                $resumeRequired = (bool) ($field['required'] ?? true);
            }
            if (($field['key'] ?? null) === 'summary') {
                $rules['summary'] = ['nullable', 'string'];
            }
        }
        $rules['resume'] = [$resumeRequired ? 'required' : 'nullable', 'file', 'mimes:'.self::RESUME_MIMES, "max:$maxKb"];

        // Custom questions.
        foreach ($schema->questions as $question) {
            /** @var QuestionData $question */
            $rules += $this->questionRules($question, $maxKb);
        }

        return $rules;
    }

    /** @return array<string, array<int, string>> */
    private function questionRules(QuestionData $question, int $maxKb): array
    {
        $key = 'question_'.$question->id;
        $required = $question->required ? 'required' : 'nullable';
        $choiceIds = array_map(fn ($c) => (string) ($c['id'] ?? ''), $question->choices);
        $in = 'in:'.implode(',', $choiceIds);

        return match ($question->type) {
            'boolean' => [$key => [$question->required ? 'accepted' : 'nullable', 'boolean']],
            'numeric' => [$key => [$required, 'numeric']],
            'date' => [$key => [$required, 'date']],
            'dropdown' => [$key => [$required, 'string', $in]],
            'multiple_choice' => $question->singleAnswer === false
                ? [$key => [$required, 'array'], "$key.*" => ['string', $in]]
                : [$key => [$required, 'string', $in]],
            'file' => [$key => [$required, 'file', "max:$maxKb"]],
            default => [$key => [$required, 'string']], // free_text, short_text
        };
    }
}
