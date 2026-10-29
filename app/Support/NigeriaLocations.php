<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Static, database-free source for Nigeria's states and Local Government Areas.
 *
 * Nigeria LGAs have no equivalent in the nnjeim/world data set, so they are kept
 * here as canonical reference data for the "state of origin" / LGA selectors and
 * their validation.
 */
class NigeriaLocations
{
    /**
     * @var array<int, array{name: string, type?: string, lgas: array<int, string>}>|null
     */
    private static ?array $data = null;

    /**
     * @return array<int, array{name: string, type?: string, lgas: array<int, string>}>
     */
    private static function data(): array
    {
        if (self::$data === null) {
            /** @var array<int, array{name: string, type?: string, lgas: array<int, string>}> $data */
            $data = require __DIR__.'/nigeria-locations.php';
            self::$data = $data;
        }

        return self::$data;
    }

    /**
     * Ordered list of state names (including the FCT).
     *
     * @return array<int, string>
     */
    public static function states(): array
    {
        return array_map(static fn (array $location): string => $location['name'], self::data());
    }

    /**
     * Ordered list of states with their LGAs, shaped for JSON responses / selects.
     *
     * @return Collection<int, array{name: string, lgas: array<int, string>}>
     */
    public static function statesWithLgas(): Collection
    {
        return collect(self::data())->map(static fn (array $location): array => [
            'name' => $location['name'],
            'lgas' => array_values($location['lgas']),
        ])->values();
    }

    /**
     * LGAs for a state, matched case-insensitively. Empty when the state is unknown.
     *
     * @return array<int, string>
     */
    public static function lgas(string $state): array
    {
        $state = mb_strtolower(trim($state));

        foreach (self::data() as $location) {
            if (mb_strtolower($location['name']) === $state) {
                return array_values($location['lgas']);
            }
        }

        return [];
    }

    public static function hasState(string $state): bool
    {
        $state = mb_strtolower(trim($state));

        foreach (self::data() as $location) {
            if (mb_strtolower($location['name']) === $state) {
                return true;
            }
        }

        return false;
    }

    public static function hasLga(string $state, string $lga): bool
    {
        $lga = mb_strtolower(trim($lga));

        foreach (self::lgas($state) as $candidate) {
            if (mb_strtolower($candidate) === $lga) {
                return true;
            }
        }

        return false;
    }
}
