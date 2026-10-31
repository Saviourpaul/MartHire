<?php

namespace App\Services;

use InvalidArgumentException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Nnjeim\World\Models\Country;
use ReflectionClass;
use Throwable;

class PhoneNumberService
{
    private ?array $countries = null;

    /** @return array{phone: string, phone_country: string, phone_dial_code: string, phone_national_number: string} */
    public function normalize(string $value, ?string $country = null): array
    {
        $value = trim($value);
        $country = $country !== null ? strtoupper(trim($country)) : null;
        $util = PhoneNumberUtil::getInstance();
        if ($value === '' || strlen($value) > 80 || ! preg_match('/^\+?[\p{Nd}\s().-]+$/uD', $value)) {
            throw new InvalidArgumentException('Enter a valid international phone number.');
        }
        if ($country !== null && ! in_array($country, $util->getSupportedRegions(), true)) {
            throw new InvalidArgumentException('Choose a valid phone country.');
        }

        try {
            $number = $util->parse($value, $country);
        } catch (NumberParseException) {
            throw new InvalidArgumentException('Enter a valid phone number for the selected country.');
        }
        $region = $country ?? $util->getRegionCodeForNumber($number);
        if (! $region || ! in_array($region, $util->getSupportedRegions(), true)
            || $number->hasExtension() || ! $util->isValidNumber($number) || ! $util->isValidNumberForRegion($number, $region)) {
            throw new InvalidArgumentException('Enter a valid phone number for the selected country.');
        }

        return [
            'phone' => $util->format($number, PhoneNumberFormat::E164),
            'phone_country' => $region,
            'phone_dial_code' => '+'.$number->getCountryCode(),
            // This preserves significant leading zeroes, including Italian numbers.
            'phone_national_number' => $util->getNationalSignificantNumber($number),
        ];
    }

    /** Normalize submitted data; derived fields never come from client input. */
    public function prepare(array $input, ?string $fallbackCountry = null): array
    {
        if (! array_key_exists('phone', $input)) {
            return [];
        }
        $parts = ['phone_country' => null, 'phone_dial_code' => null, 'phone_national_number' => null];
        if ($input['phone'] === null || (is_string($input['phone']) && trim($input['phone']) === '')) {
            return ['phone' => null, ...$parts];
        }
        if (! is_string($input['phone'])) {
            return $parts;
        }
        $explicit = array_key_exists('phone_country', $input);
        $country = $explicit ? $input['phone_country'] : (str_starts_with(trim($input['phone']), '+') ? null : $fallbackCountry);
        if ($country !== null && ! is_string($country)) {
            return ['phone_country' => $country, 'phone_dial_code' => null, 'phone_national_number' => null];
        }
        try {
            return $this->normalize($input['phone'], $country);
        } catch (InvalidArgumentException) {
            return ['phone_country' => is_string($country) ? strtoupper(trim($country)) : null, 'phone_dial_code' => null, 'phone_national_number' => null];
        }
    }

    /** @return list<array{code: string, name: string, dial_code: string, flag_url: ?string}> */
    public function countries(): array
    {
        if ($this->countries !== null) {
            return $this->countries;
        }
        $reference = app(JobCountryService::class);
        $world = $reference->all();
        $path = dirname((new ReflectionClass(Country::class))->getFileName(), 3).'/resources/json/countries.json';
        try {
            $names = is_file($path) ? array_column(json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR), 'name', 'iso2') : [];
        } catch (Throwable) {
            $names = [];
        }
        $names += ['AC' => 'Ascension Island', 'TA' => 'Tristan da Cunha'];
        $util = PhoneNumberUtil::getInstance();
        $this->countries = [];
        foreach ($util->getSupportedRegions() as $code) {
            $this->countries[] = [
                'code' => $code,
                'name' => $world[$code]['name'] ?? $names[$code] ?? $code,
                'dial_code' => '+'.$util->getCountryCodeForRegion($code),
                'flag_url' => $reference->flagUrl($code),
            ];
        }
        usort($this->countries, fn (array $first, array $second): int => strcasecmp($first['name'], $second['name']));

        return $this->countries;
    }
}
