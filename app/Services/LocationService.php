<?php

namespace App\Services;

use App\Exceptions\LocationDataUnavailableException;
use App\Support\NigeriaLocations;
use App\Support\WorldLocations;
use Closure;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Throwable;

/**
 * Unified country -> state -> city lookup.
 *
 * Nigeria (NG) is served from the static {@see NigeriaLocations} data set, where
 * the "cities" of a state are its Local Government Areas. Every other country is
 * served from the nnjeim/world data set via {@see WorldLocations}. Results are
 * returned as {value, label} option pairs and cached when a cache store is
 * available (degrading gracefully otherwise).
 */
class LocationService
{
    private const NIGERIA_ISO2 = 'NG';

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function states(string $countryIso2): array
    {
        try {
            return $this->stateOptions($countryIso2)['data'];
        } catch (InvalidArgumentException|LocationDataUnavailableException) {
            // Hydrate the page; the selector's JSON request reports any outage.
            return [];
        }
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function cities(string $countryIso2, string $state): array
    {
        try {
            return $this->cityOptions($countryIso2, $state)['data'];
        } catch (InvalidArgumentException|LocationDataUnavailableException) {
            return [];
        }
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function countries(): array
    {
        try {
            WorldLocations::assertAvailable('countries');
            $countries = $this->remember('countries', fn (): array => array_map(
                static fn (array $country): array => ['value' => $country['iso2'], 'label' => $country['name']],
                WorldLocations::countries()
            ));
        } catch (LocationDataUnavailableException) {
            $countries = [];
        }

        // Nigerian state/LGA reference data is independent of World's tables.
        if (! collect($countries)->contains(fn (array $country): bool => $country['value'] === self::NIGERIA_ISO2)) {
            array_unshift($countries, ['value' => self::NIGERIA_ISO2, 'label' => 'Nigeria']);
        }

        return $countries;
    }

    /** @return array{data: array<int, array{value: string, label: string}>, meta: array{manual: bool}} */
    public function stateOptions(string $countryIso2): array
    {
        $iso2 = strtoupper(trim($countryIso2));
        if (! $this->countryIsValid($iso2)) {
            throw new InvalidArgumentException('Select a valid country.');
        }

        if ($iso2 === self::NIGERIA_ISO2) {
            return ['data' => $this->options(NigeriaLocations::states()), 'meta' => ['manual' => false]];
        }

        WorldLocations::assertAvailable('states');
        $options = $this->remember("states:{$iso2}", fn (): array => $this->options(array_column(WorldLocations::states($iso2), 'name')));

        return ['data' => $options, 'meta' => ['manual' => $options === []]];
    }

    /** @return array{data: array<int, array{value: string, label: string}>, meta: array{manual: bool}} */
    public function cityOptions(string $countryIso2, string $state): array
    {
        $iso2 = strtoupper(trim($countryIso2));
        $state = trim($state);
        if (! $this->stateIsValid($iso2, $state)) {
            throw new InvalidArgumentException('Select a state that belongs to the selected country.');
        }

        if ($iso2 === self::NIGERIA_ISO2) {
            return ['data' => $this->options(NigeriaLocations::lgas($state)), 'meta' => ['manual' => false]];
        }

        WorldLocations::assertAvailable('cities');
        $options = $this->remember("cities:{$iso2}:".sha1($state), fn (): array => $this->options(array_column(WorldLocations::citiesForState($iso2, $state), 'name')));

        return ['data' => $options, 'meta' => ['manual' => $options === []]];
    }

    public function countryIsValid(string $iso2): bool
    {
        $iso2 = strtoupper(trim($iso2));

        return preg_match('/^[A-Z]{2}$/D', $iso2) === 1
            && ($iso2 === self::NIGERIA_ISO2 || WorldLocations::countryExists($iso2));
    }

    /** Authorize manual state names only for a known country with zero reference states. */
    public function stateIsValid(string $iso2, string $state): bool
    {
        $iso2 = strtoupper(trim($iso2));
        if (trim($state) === '' || ! $this->countryIsValid($iso2)) {
            return false;
        }

        if ($iso2 === self::NIGERIA_ISO2) {
            return NigeriaLocations::hasState($state);
        }

        return WorldLocations::stateExists($iso2, $state) || WorldLocations::states($iso2) === [];
    }

    /** Live reference queries keep validation authoritative even when option lists are cached. */
    public function cityIsValid(string $iso2, string $state, string $city): bool
    {
        $iso2 = strtoupper(trim($iso2));
        if (trim($city) === '' || ! $this->stateIsValid($iso2, $state)) {
            return false;
        }

        if ($iso2 === self::NIGERIA_ISO2) {
            return NigeriaLocations::hasLga($state, $city);
        }

        WorldLocations::assertAvailable('cities');

        return WorldLocations::cityExists($iso2, $state, $city) || WorldLocations::citiesForState($iso2, $state) === [];
    }

    /** @return array<int, array{value: string, label: string}> */
    public function jobCountries(): array
    {
        return array_values(array_map(
            static fn (array $country): array => ['value' => $country['code'], 'label' => $country['name']],
            app(JobCountryService::class)->all(),
        ));
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, array{value: string, label: string}>
     */
    private function options(array $names): array
    {
        return array_map(static fn (string $name): array => ['value' => $name, 'label' => $name], array_values(array_unique($names)));
    }

    /**
     * @param  Closure(): array<int, array{value: string, label: string}>  $callback
     * @return array<int, array{value: string, label: string}>
     */
    private function remember(string $key, Closure $callback): array
    {
        $store = null;
        try {
            $store = Cache::store(config('locations.cache.driver'));
            $key = config('locations.cache.prefix', 'nearbyworks.locations.v2').':'.WorldLocations::cacheIdentity().':'.$key;
            $cached = $store->get($key);
            if (is_array($cached) && $cached !== []) {
                return $cached;
            }
        } catch (Throwable) {
            $store = null;
        }

        $options = $callback();
        // Never turn missing/initially unseeded data into a stale empty selector.
        if ($store !== null && $options !== []) {
            try {
                $store->put($key, $options, max(1, (int) config('locations.cache.ttl', 3600)));
            } catch (Throwable) {
                // Cache availability does not affect reference correctness.
            }
        }

        return $options;
    }
}
