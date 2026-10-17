<?php

namespace App\Http\Requests\Concerns;

use App\Support\NigeriaLocations;
use App\Support\WorldLocations;
use Closure;

/**
 * Shared country -> state -> city validation for location-bearing forms.
 *
 * Nigeria is validated against the static {@see NigeriaLocations} data (where a
 * state's "cities" are its Local Government Areas); every other country is
 * validated against the nnjeim/world data via {@see WorldLocations}, degrading
 * gracefully when the world tables are not available.
 *
 * For Nigeria the selected city (an LGA) is mirrored into the legacy
 * local_government_area column; for other countries that column is cleared.
 */
trait ValidatesLocationSelection
{
    protected const NIGERIA_ISO2 = 'NG';

    protected function selectedCountryIso2(): string
    {
        return mb_strtoupper(trim((string) $this->input('country_code')));
    }

    protected function isNigeriaSelected(): bool
    {
        return $this->selectedCountryIso2() === self::NIGERIA_ISO2;
    }

    /**
     * @param  array<int, string>  $presence  e.g. ['required'] or ['nullable']
     * @return array<string, array<int, mixed>>
     */
    protected function locationSelectionRules(array $presence): array
    {
        return [
            'country_code' => [...$presence, 'string', 'size:2', $this->countryRule()],
            'country' => [...$presence, 'string', 'max:255'],
            'state' => [...$presence, 'string', 'max:255', $this->stateRule()],
            'city' => [...$presence, 'string', 'max:255', $this->cityRule()],
            'local_government_area' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function mirrorNigerianLocalGovernmentArea(): void
    {
        $this->merge([
            'local_government_area' => $this->isNigeriaSelected() ? $this->input('city') : null,
        ]);
    }

    protected function countryRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && $value !== '' && ! WorldLocations::countryExists($value)) {
                $fail('Select a valid country.');
            }
        };
    }

    protected function stateRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            $valid = $this->isNigeriaSelected()
                ? NigeriaLocations::hasState($value)
                : WorldLocations::stateExists($this->selectedCountryIso2(), $value);

            if (! $valid) {
                $fail('Select a state that belongs to the selected country.');
            }
        };
    }

    protected function cityRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || $value === '' || ! $this->filled('state')) {
                return;
            }

            $state = (string) $this->input('state');

            if ($this->isNigeriaSelected()) {
                if (! NigeriaLocations::hasLga($state, $value)) {
                    $fail('Select a local government area in the selected state.');
                }

                return;
            }

            if (! WorldLocations::cityExists($state, $value)) {
                $fail('Select a city that belongs to the selected state.');
            }
        };
    }
}
