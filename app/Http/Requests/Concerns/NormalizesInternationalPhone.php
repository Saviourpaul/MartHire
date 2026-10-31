<?php

namespace App\Http\Requests\Concerns;

use App\Services\PhoneNumberService;

trait NormalizesInternationalPhone
{
    protected function normalizePhoneInput(): void
    {
        unset($this['phone_dial_code'], $this['phone_national_number']);
        if (! $this->has('phone')) {
            unset($this['phone_country']);
        }
        $fallback = $this->user()?->phone_country ?: $this->input('country_code', $this->user()?->country_code);
        $fallback = is_string($fallback) && $fallback !== '' ? $fallback : config('phone.default_country', 'NG');
        $this->merge(app(PhoneNumberService::class)->prepare($this->all(), $fallback));
    }

    protected function phonePartRules(): array
    {
        return [
            'phone_country' => ['nullable', 'string', 'size:2'],
            'phone_dial_code' => ['nullable', 'string', 'max:8'],
            'phone_national_number' => ['nullable', 'string', 'max:20'],
        ];
    }
}
