<?php

namespace App\Actions\Workable;

use App\Integrations\Workable\DTO\ApplicationSchema;
use App\Integrations\Workable\DTO\QuestionData;

/**
 * Builds the Workable create-candidate JSON body from validated form data
 * (design §7.4 / §2). Pure: no file IO — base64 file contents are passed in via
 * $files keyed by the form field name (e.g. 'resume', 'question_123'). Only
 * schema-declared fields are emitted; `sourced` is false (an applied candidate).
 *
 * @phpstan-type FileRef array{name: string, data: string}
 */
class PayloadBuilder
{
    /**
     * @param  array<string, mixed>  $data   validated form input
     * @param  array<string, array{name: string, data: string}>  $files  base64 file refs by field name
     * @return array<string, mixed>
     */
    public function build(ApplicationSchema $schema, array $data, array $files = []): array
    {
        $candidate = [
            'firstname' => $data['first_name'],
            'lastname' => $data['last_name'],
            'email' => $data['email'],
        ];

        foreach (['phone', 'cover_letter', 'summary'] as $optional) {
            if (! empty($data[$optional])) {
                $candidate[$optional] = $data[$optional];
            }
        }

        if (isset($files['resume'])) {
            $candidate['resume'] = ['name' => $files['resume']['name'], 'data' => $files['resume']['data']];
        }

        $answers = [];
        foreach ($schema->questions as $question) {
            /** @var QuestionData $question */
            $answer = $this->answerFor($question, $data, $files);
            if ($answer !== null) {
                $answers[] = $answer;
            }
        }

        if ($answers !== []) {
            $candidate['answers'] = $answers;
        }

        return ['sourced' => false, 'candidate' => $candidate];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array{name: string, data: string}>  $files
     * @return array<string, mixed>|null
     */
    private function answerFor(QuestionData $question, array $data, array $files): ?array
    {
        $key = 'question_'.$question->id;
        $base = ['question_key' => $question->id];

        if ($question->type === 'file') {
            return isset($files[$key])
                ? $base + ['file' => ['name' => $files[$key]['name'], 'data' => $files[$key]['data']]]
                : null;
        }

        if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            return null;
        }

        $value = $data[$key];

        return match ($question->type) {
            'boolean' => $base + ['checked' => (bool) $value],
            'numeric' => $base + ['value' => $value + 0],
            'date' => $base + ['date' => $value],
            'dropdown' => $base + ['choices' => [(string) $value]],
            'multiple_choice' => $base + ['choices' => array_map('strval', (array) $value)],
            default => $base + ['body' => (string) $value], // free_text, short_text
        };
    }
}
