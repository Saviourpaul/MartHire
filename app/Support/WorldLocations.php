<?php

namespace App\Support;

use App\Exceptions\LocationDataUnavailableException;
use Illuminate\Database\Eloquent\Model;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;
use Throwable;

/**
 * Read World's reference records through its configured model, connection and
 * table. A genuine empty result is distinct from an unavailable data source.
 */
class WorldLocations
{
    public static function available(): bool
    {
        try {
            foreach (['countries', 'states', 'cities'] as $kind) {
                self::assertAvailable($kind);
            }

            return true;
        } catch (LocationDataUnavailableException) {
            return false;
        }
    }

    public static function countryExists(string $iso2): bool
    {
        return self::read(fn (): bool => self::model('countries')->newQuery()
            ->where('iso2', strtoupper(trim($iso2)))->exists());
    }

    public static function stateExists(?string $iso2, string $state): bool
    {
        if (blank($iso2) || trim($state) === '') {
            return false;
        }

        return self::read(function () use ($iso2, $state): bool {
            $country = self::country($iso2);

            return $country !== null && self::model('states')->newQuery()
                ->where('country_id', $country->getKey())->where('name', trim($state))->exists();
        });
    }

    public static function cityExists(string $iso2, string $state, string $city): bool
    {
        return self::read(function () use ($iso2, $state, $city): bool {
            $parent = self::state($iso2, $state);

            return $parent !== null && self::model('cities')->newQuery()
                ->where('state_id', $parent->getKey())
                ->where('country_id', $parent->getAttribute('country_id'))
                ->where('name', trim($city))->exists();
        });
    }

    /**
     * @return array<int, array{iso2: string, name: string}>
     */
    public static function countries(): array
    {
        return self::read(fn (): array => self::model('countries')->newQuery()->orderBy('name')
            ->get(['iso2', 'name'])->map(fn (Model $country): array => [
                'iso2' => $country->getAttribute('iso2'), 'name' => $country->getAttribute('name'),
            ])->all());
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function states(string $iso2): array
    {
        return self::read(function () use ($iso2): array {
            $country = self::country($iso2);
            $states = self::model('states');
            if ($country === null) {
                return [];
            }

            return $states->newQuery()->where('country_id', $country->getKey())->orderBy('name')
                ->get(['id', 'name'])->map(fn (Model $state): array => [
                    'id' => (int) $state->getKey(), 'name' => $state->getAttribute('name'),
                ])->all();
        });
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function cities(int $stateId): array
    {
        return self::read(function () use ($stateId): array {
            $cities = self::model('cities');
            $state = self::model('states')->newQuery()->find($stateId);
            if ($state === null) {
                return [];
            }

            return $cities->newQuery()->where('state_id', $stateId)
                ->where('country_id', $state->getAttribute('country_id'))->orderBy('name')->get(['id', 'name'])
                ->map(fn (Model $city): array => [
                    'id' => (int) $city->getKey(), 'name' => $city->getAttribute('name'),
                ])->all();
        });
    }

    /** @return array<int, array{id: int, name: string}> */
    public static function citiesForState(string $iso2, string $state): array
    {
        return self::read(function () use ($iso2, $state): array {
            $parent = self::state($iso2, $state);
            self::assertAvailable('cities');

            return $parent === null ? [] : self::cities((int) $parent->getKey());
        });
    }

    public static function assertAvailable(string $kind): void
    {
        self::model($kind);
    }

    public static function cacheIdentity(): string
    {
        return self::read(function (): string {
            $identity = [];
            foreach (['countries', 'states', 'cities'] as $kind) {
                $model = self::configuredModel($kind);
                $identity[$kind] = [get_class($model), $model->getConnectionName(), $model->getTable(), $model->getConnection()->getDatabaseName()];
            }

            return sha1(json_encode($identity));
        });
    }

    private static function country(string $iso2): ?Model
    {
        return self::model('countries')->newQuery()->where('iso2', strtoupper(trim($iso2)))->first();
    }

    private static function state(string $iso2, string $name): ?Model
    {
        $country = self::country($iso2);
        $states = self::model('states');

        return $country === null ? null : $states->newQuery()
            ->where('country_id', $country->getKey())->where('name', trim($name))->first();
    }

    private static function configuredModel(string $kind): Model
    {
        $fallback = match ($kind) {
            'countries' => Country::class, 'states' => State::class, 'cities' => City::class,
            default => throw new LocationDataUnavailableException('Unknown location reference type.'),
        };
        $class = config('world.models.'.$kind, $fallback);
        $model = new $class;
        if (! $model instanceof Model) {
            throw new LocationDataUnavailableException('The location reference model is unavailable.');
        }

        return $model;
    }

    private static function model(string $kind): Model
    {
        return self::read(function () use ($kind): Model {
            $model = self::configuredModel($kind);
            $schema = $model->getConnection()->getSchemaBuilder();
            $columns = match ($kind) {
                'countries' => [$model->getKeyName(), 'iso2', 'name'],
                'states' => [$model->getKeyName(), 'country_id', 'name'],
                'cities' => [$model->getKeyName(), 'country_id', 'state_id', 'name'],
            };
            if (! $schema->hasTable($model->getTable()) || ! $schema->hasColumns($model->getTable(), $columns)) {
                throw new LocationDataUnavailableException('Location reference data is not installed.');
            }

            return $model;
        });
    }

    private static function read(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (LocationDataUnavailableException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new LocationDataUnavailableException('Location reference data is temporarily unavailable.', previous: $exception);
        }
    }
}
