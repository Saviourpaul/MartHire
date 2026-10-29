<?php

namespace App\Integrations\Workable\DTO;

final class SubmissionResult
{
    public function __construct(
        public readonly int $status,
        public readonly ?string $candidateId = null,
    ) {}
}
