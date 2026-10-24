<?php

namespace App\Integrations\Workable\Exceptions;

use Throwable;

class RateLimited extends WorkableException
{
    public readonly ?int $retryAfter;

    public function __construct(?int $retryAfter = null, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $this->retryAfter = $retryAfter;

        parent::__construct($message, $code, $previous);
    }

    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
