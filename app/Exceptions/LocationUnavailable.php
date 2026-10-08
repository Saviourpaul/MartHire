<?php

namespace App\Exceptions;

use RuntimeException;

class LocationUnavailable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Location lists are temporarily unavailable. Please try again shortly.');
    }
}
