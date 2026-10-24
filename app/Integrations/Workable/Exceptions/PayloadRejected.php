<?php

namespace App\Integrations\Workable\Exceptions;

use Throwable;

class PayloadRejected extends WorkableException
{
    public readonly array $validationErrors;

    public function __construct(array $validationErrors = [], string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $this->validationErrors = $validationErrors;

        parent::__construct($message, $code, $previous);
    }

    public function validationErrors(): array
    {
        return $this->validationErrors;
    }
}
