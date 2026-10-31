<?php

namespace App\Rules;

use App\Services\PhoneNumberService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class InternationalPhoneNumber implements ValidationRule
{
    public function __construct(private readonly mixed $country = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ($this->country !== null && ! is_string($this->country))) {
            $fail('Enter a valid phone number and choose its country.');

            return;
        }
        try {
            app(PhoneNumberService::class)->normalize($value, $this->country);
        } catch (InvalidArgumentException $exception) {
            $fail($exception->getMessage());
        }
    }
}
