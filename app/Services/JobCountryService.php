<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nnjeim\World\CountryFlag;
use Nnjeim\World\Models\Country;
use ReflectionClass;
use Throwable;

/** Country reference data shared by job validation, public listings and the map. */
class JobCountryService
{
    /** @var array<string, array{code: string, name: string, latitude: ?float, longitude: ?float, flag_url: ?string, flag_emoji: string}>|null */
    private ?array $countries = null;

    /** @var array<string, array{latitude: mixed, longitude: mixed}>|null */
    private ?array $coordinates = null;

    /**
     * Only installed World records are selectable. Missing reference data must
     * not silently make an arbitrary submitted country code valid.
     *
     * @return array<string, array{code: string, name: string, latitude: ?float, longitude: ?float, flag_url: ?string, flag_emoji: string}>
     */
    public function all(): array
    {
        if ($this->countries !== null) {
            return $this->countries;
        }

        $this->countries = [];

        try {
            $modelClass = config('world.models.countries', Country::class);
            $model = new $modelClass;

            if (! $model instanceof Model || ! $model->getConnection()->getSchemaBuilder()->hasTable($model->getTable())) {
                return $this->countries;
            }

            // Optional World columns (coordinates, emoji) may not be installed.
            // Read the available attributes once rather than querying per job.
            foreach ($model->newQuery()->orderBy('name')->get() as $country) {
                $code = strtoupper(trim((string) $country->getAttribute('iso2')));
                $name = trim((string) $country->getAttribute('name'));

                if (! preg_match('/^[A-Z]{2}$/D', $code) || $name === '') {
                    continue;
                }

                $attributes = $country->getAttributes();
                $coordinates = $this->bundledCoordinates()[$code] ?? [];
                $this->countries[$code] = [
                    'code' => $code,
                    'name' => $name,
                    'latitude' => $this->coordinate($attributes['latitude'] ?? null, 90)
                        ?? $this->coordinate($coordinates['latitude'] ?? null, 90),
                    'longitude' => $this->coordinate($attributes['longitude'] ?? null, 180)
                        ?? $this->coordinate($coordinates['longitude'] ?? null, 180),
                    'flag_url' => $this->flagUrl($code),
                    'flag_emoji' => CountryFlag::emoji($code) ?? '',
                ];
            }
        } catch (Throwable) {
            // A missing/unavailable World connection leaves job validation closed.
            $this->countries = [];
        }

        return $this->countries;
    }

    /** @return array{code: string, name: string, latitude: ?float, longitude: ?float, flag_url: ?string, flag_emoji: string}|null */
    public function find(?string $value): ?array
    {
        $value = trim($value ?? '');

        if ($value === '') {
            return null;
        }

        $countries = $this->all();
        $code = strtoupper($value);

        if (isset($countries[$code])) {
            return $countries[$code];
        }

        $name = Str::lower(Str::ascii($value));

        foreach ($countries as $country) {
            if (Str::lower(Str::ascii($country['name'])) === $name) {
                return $country;
            }
        }

        return null;
    }

    /** Resolve every flag through the same safe, lowercase local asset path. */
    public function flagUrl(?string $code): ?string
    {
        $code = strtolower(trim($code ?? ''));
        if (! preg_match('/^[a-z]{2}$/D', $code)) {
            return null;
        }

        $path = 'asset/marthire/flags/'.$code.'.svg';

        return is_file(public_path($path)) ? asset($path) : null;
    }

    private function coordinate(mixed $value, int $limit): ?float
    {
        return is_numeric($value) && abs((float) $value) <= $limit ? (float) $value : null;
    }

    /** @return array<string, array{latitude: mixed, longitude: mixed}> */
    private function bundledCoordinates(): array
    {
        if ($this->coordinates !== null) {
            return $this->coordinates;
        }

        $this->coordinates = [];

        try {
            // World makes these database columns optional. Its bundled reference
            // metadata supplies map coordinates, never extra selectable countries.
            $path = dirname((new ReflectionClass(Country::class))->getFileName(), 3).'/resources/json/countries.json';

            if (is_file($path)) {
                foreach (json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) as $country) {
                    $this->coordinates[$country['iso2']] = [
                        'latitude' => $country['latitude'] ?? null,
                        'longitude' => $country['longitude'] ?? null,
                    ];
                }
            }
        } catch (Throwable) {
            $this->coordinates = [];
        }

        return $this->coordinates;
    }
}
