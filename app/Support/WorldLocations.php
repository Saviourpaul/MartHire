<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

/**
 * Thin, fault-tolerant accessor over the nnjeim/world data set used for the
 * country -> state -> city selectors and their validation.
 *
 * Every lookup degrades gracefully when the world tables are not present or not
 * yet seeded: validation passes (so the app never hard-fails on missing package
 * data) and data accessors return empty lists.
 */
class WorldLocations
{
    public static function available(): bool
    {
        return Schema::hasTable('countries') && Schema::hasTable('states') && Schema::hasTable('cities');
    }

    public static function countryExists(string $iso2): bool
    {
        if (! Schema::hasTable('countries')) {
            return true;
        }

        return Country::query()->where('iso2', mb_strtoupper(trim($iso2)))->exists();
    }

    public static function stateExists(?string $iso2, string $state): bool
    {
        if (! Schema::hasTable('states')) {
            return true;
        }

        $query = State::query()->where('name', trim($state));

        if ($iso2 !== null && $iso2 !== '' && Schema::hasTable('countries')) {
            $country = Country::query()->where('iso2', mb_strtoupper(trim($iso2)))->first();

            if ($country !== null) {
                $query->where('country_id', $country->getKey());
            }
        }

        return $query->exists();
    }

    public static function cityExists(string $state, string $city): bool
    {
        if (! Schema::hasTable('cities')) {
            return true;
        }

        return City::query()
            ->where('name', trim($city))
            ->whereHas('state', fn ($query) => $query->where('name', trim($state)))
            ->exists();
    }

    /**
     * @return array<int, array{iso2: string, name: string}>
     */
    public static function countries(): array
    {
        if (! Schema::hasTable('countries')) {
            return [];
        }

        return Country::query()
            ->orderBy('name')
            ->get(['iso2', 'name'])
            ->map(fn (Country $country): array => ['iso2' => $country->iso2, 'name' => $country->name])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function states(string $iso2): array
    {
        if (! self::available()) {
            return [];
        }

        $country = Country::query()->where('iso2', mb_strtoupper(trim($iso2)))->first();

        if ($country === null) {
            return [];
        }

        return State::query()
            ->where('country_id', $country->getKey())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (State $state): array => ['id' => (int) $state->getKey(), 'name' => $state->name])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function cities(int $stateId): array
    {
        if (! Schema::hasTable('cities')) {
            return [];
        }

        return City::query()
            ->where('state_id', $stateId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (City $city): array => ['id' => (int) $city->getKey(), 'name' => $city->name])
            ->all();
    }
}
