<?php

namespace App\Integrations\Workable\DTO;

final class ApplicationSchema
{
    /**
     * @param  array<int, array<string, mixed>>  $formFields
     * @param  array<int, QuestionData>  $questions
     */
    public function __construct(
        public readonly array $formFields,
        public readonly array $questions,
    ) {}

    public static function fromApi(array $form): self
    {
        $fields = [];
        foreach ($form['form_fields'] ?? [] as $f) {
            $field = [
                'key' => (string) ($f['key'] ?? ''),
                'label' => (string) ($f['label'] ?? ''),
                'type' => (string) ($f['type'] ?? ''),
                'required' => (bool) ($f['required'] ?? false),
            ];
            foreach (['supported_file_types', 'max_file_size', 'fields'] as $extra) {
                if (array_key_exists($extra, $f)) {
                    $field[$extra] = $f[$extra];
                }
            }
            $fields[] = $field;
        }

        $questions = array_map(
            fn (array $q) => QuestionData::fromApi($q),
            array_values($form['questions'] ?? []),
        );

        return new self($fields, $questions);
    }
}
