<?php

namespace App\Http\Requests\Concerns;

use App\Exceptions\LocationDataUnavailableException;
use App\Services\LocationService;
use App\Support\NigeriaLocations;
use Closure;

/**
 * Shared country -> state -> city/LGA validation for location-bearing forms.
 *
 * Nigeria is validated against the static {@see NigeriaLocations} data (where a
 * state's "cities" are its Local Government Areas); every other country is
 * validated against {@see LocationService}. Manual names are allowed only when
 * the selected parent has no reference rows; an unavailable source fails closed.
 *
 * Two field shapes are supported:
 *  - {@see locationSelectionRules()} — a single City/LGA selector that doubles
 *    as the LGA for Nigeria (used by the profile form). Pair with
 *    {@see mirrorNigerianLocalGovernmentArea()}.
 *  - {@see exclusiveLocationSelectionRules()} — a dedicated City field and a
 *    dedicated Local Government Area field that are mutually exclusive by
 *    country (used by the job application form). Pair with
 *    {@see normalizeLocationForCountry()}.
 */
trait ValidatesLocationSelection
{
    protected const NIGERIA_ISO2 = 'NG';

    protected function selectedCountryIso2(): string
    {
        $code = $this->input('country_code');

        return is_string($code) ? strtoupper(trim($code)) : '';
    }

    protected function isNigeriaSelected(): bool
    {
        return $this->selectedCountryIso2() === self::NIGERIA_ISO2;
    }

    /**
     * Single City/LGA selector: the city value doubles as the LGA for Nigeria.
     *
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

    /**
     * Dedicated City and Local Government Area fields, mutually exclusive by
     * country: Nigeria requires the LGA (City nullable); every other country
     * requires the City (LGA nullable).
     *
     * @param  array<int, string>  $presence  e.g. ['required'] or ['nullable']
     * @return array<string, array<int, mixed>>
     */
    protected function exclusiveLocationSelectionRules(array $presence): array
    {
        $nigeria = $this->isNigeriaSelected();

        return [
            'country_code' => [...$presence, 'string', 'size:2', $this->countryRule()],
            'country' => [...$presence, 'string', 'max:255'],
            'state' => [...$presence, 'string', 'max:255', $this->stateRule()],
            'city' => $nigeria
                ? ['nullable', 'string', 'max:255']
                : [...$presence, 'string', 'max:255', $this->cityRule()],
            'local_government_area' => $nigeria
                ? [...$presence, 'string', 'max:255', $this->localGovernmentAreaRule()]
                : ['nullable', 'string', 'max:255'],
        ];
    }

    protected function mirrorNigerianLocalGovernmentArea(): void
    {
        $this->merge([
            'local_government_area' => $this->isNigeriaSelected() ? $this->input('city') : null,
        ]);
    }

    /**
     * Keep the mutually exclusive City / LGA columns clean for the chosen country.
     */
    protected function normalizeLocationForCountry(): void
    {
        $this->merge($this->isNigeriaSelected()
            ? ['city' => null]
            : ['local_government_area' => null]);
    }

    protected function countryRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            try {
                if (! app(LocationService::class)->countryIsValid($value)) {
                    $fail('Select a valid country.');
                }
            } catch (LocationDataUnavailableException) {
                $fail('Country data is temporarily unavailable. Please try again.');
            }
        };
    }

    protected function stateRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            try {
                if (! app(LocationService::class)->stateIsValid($this->selectedCountryIso2(), $value)) {
                    $fail('Select a state that belongs to the selected country.');
                }
            } catch (LocationDataUnavailableException) {
                $fail('State data is temporarily unavailable. Please try again.');
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

            try {
                if (! app(LocationService::class)->cityIsValid($this->selectedCountryIso2(), $state, $value)) {
                    $fail($this->isNigeriaSelected()
                        ? 'Select a local government area in the selected state.'
                        : 'Select a city that belongs to the selected state.');
                }
            } catch (LocationDataUnavailableException) {
                $fail('City data is temporarily unavailable. Please try again.');
            }
        };
    }

    protected function localGovernmentAreaRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || $value === '' || ! $this->filled('state')) {
                return;
            }

            if (! NigeriaLocations::hasLga((string) $this->input('state'), $value)) {
                $fail('Select a local government area in the selected state.');
            }
        };
    }
}
