<?php

namespace App\Services;

use App\Support\NigeriaLocations;
use App\Support\WorldLocations;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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
        $iso2 = mb_strtoupper(trim($countryIso2));

        if ($iso2 === '') {
            return [];
        }

        return $this->remember("states:{$iso2}", function () use ($iso2): array {
            if ($iso2 === self::NIGERIA_ISO2) {
                return $this->options(NigeriaLocations::states());
            }

            return $this->options(array_column(WorldLocations::states($iso2), 'name'));
        });
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function cities(string $countryIso2, string $state): array
    {
        $iso2 = mb_strtoupper(trim($countryIso2));
        $state = trim($state);

        if ($iso2 === '' || $state === '') {
            return [];
        }

        return $this->remember("cities:{$iso2}:".Str::slug($state), function () use ($iso2, $state): array {
            if ($iso2 === self::NIGERIA_ISO2) {
                return $this->options(NigeriaLocations::lgas($state));
            }

            $match = collect(WorldLocations::states($iso2))->firstWhere('name', $state);

            if ($match === null) {
                return [];
            }

            return $this->options(array_column(WorldLocations::cities($match['id']), 'name'));
        });
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function countries(): array
    {
        return $this->remember('countries', function (): array {
            $countries = array_map(
                static fn (array $country): array => ['value' => $country['iso2'], 'label' => $country['name']],
                WorldLocations::countries()
            );

            // Nigeria is always selectable (it is served from static data), even
            // when the world tables are not seeded.
            if (! collect($countries)->contains(fn (array $country): bool => $country['value'] === self::NIGERIA_ISO2)) {
                array_unshift($countries, ['value' => self::NIGERIA_ISO2, 'label' => 'Nigeria']);
            }

            return $countries;
        });
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, array{value: string, label: string}>
     */
    private function options(array $names): array
    {
        return array_map(static fn (string $name): array => ['value' => $name, 'label' => $name], array_values($names));
    }

    /**
     * @param  Closure(): array<int, array{value: string, label: string}>  $callback
     * @return array<int, array{value: string, label: string}>
     */
    private function remember(string $key, Closure $callback): array
    {
        try {
            $store = Cache::store(config('locations.cache.driver'));

            return $store->remember(
                config('locations.cache.prefix').':'.$key,
                (int) config('locations.cache.ttl'),
                $callback
            );
        } catch (Throwable) {
            return $callback();
        }
    }
}
