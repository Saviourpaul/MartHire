<?php

namespace App\Integrations\Workable\DTO;

final class QuestionData
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $type,
        public readonly bool $required,
        public readonly ?bool $singleAnswer,
        public readonly array $choices,
        public readonly array $supportedFileTypes,
        public readonly ?int $maxFileSize,
    ) {}

    public static function fromApi(array $q): self
    {
        return new self(
            id: (string) ($q['id'] ?? ''),
            label: (string) ($q['body'] ?? ''),
            type: (string) ($q['type'] ?? ''),
            required: (bool) ($q['required'] ?? false),
            singleAnswer: $q['single_answer'] ?? null,
            choices: $q['choices'] ?? [],
            supportedFileTypes: $q['supported_file_types'] ?? [],
            maxFileSize: $q['max_file_size'] ?? null,
        );
    }
}
