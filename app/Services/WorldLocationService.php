<?php

namespace App\Services;

use App\Exceptions\LocationUnavailable;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @phpstan-type LocationOption array{value: string, label: string, upstream_id?: int}
 * @phpstan-type LocationCatalog array{data: list<LocationOption>, meta: array{available: bool, source: string, empty: bool, fetched_at: int|null}}
 */
class WorldLocationService
{
    public function __construct(private LocationSnapshots $snapshots) {}

    /** @return LocationCatalog */
    public function countries(): array
    {
        return $this->catalog('countries');
    }

    /** @return LocationCatalog */
    public function states(string $country): array
    {
        $country = strtoupper($country);
        $this->find($this->countries(), $country, 'country_code');

        return $this->catalog('states', $country);
    }

    /** @return LocationCatalog */
    public function cities(string $country, string $state): array
    {
        $country = strtoupper($country);
        $selected = $this->find($this->states($country), $state, 'state');

        // Snapshot IDs must never be used in upstream requests.
        return $this->catalog('cities', $country, $selected['value'], $selected['upstream_id'] ?? null);
    }

    /** @return array{country_code: string, country: string, state: string|null, city: string|null} */
    public function validateSelection(string $country, ?string $state, ?string $city): array
    {
        $code = strtoupper($country);
        $selectedCountry = $this->find($this->countries(), $code, 'country_code');
        $states = $this->states($code);
        $canonicalState = null;
        $canonicalCity = null;
        if ($states['data'] !== []) {
            $canonicalState = $this->find($states, $state ?? '', 'state')['value'];
            $cities = $this->cities($code, $canonicalState);
            if ($cities['data'] !== []) {
                $canonicalCity = $this->find($cities, $city ?? '', 'city')['value'];
            } elseif (filled($city)) {
                throw ValidationException::withMessages(['city' => 'There are no listed cities for the selected state.']);
            }
        } elseif (filled($state) || filled($city)) {
            throw ValidationException::withMessages(['state' => 'There are no listed states for the selected country.']);
        }

        return ['country_code' => $code, 'country' => $selectedCountry['label'], 'state' => $canonicalState, 'city' => $canonicalCity];
    }

    /**
     * @param  LocationCatalog  $result
     * @return LocationCatalog
     */
    public function publicResult(array $result): array
    {
        $result['data'] = array_map(fn ($row) => ['value' => $row['value'], 'label' => $row['label']], $result['data']);

        return $result;
    }

    public function cache(): Repository
    {
        return Cache::build(config('locations.cache'));
    }

    /**
     * @param  LocationCatalog  $result
     * @return LocationOption
     */
    private function find(array $result, string $value, string $field): array
    {
        foreach ($result['data'] as $row) {
            if ($row['value'] === $value) {
                return $row;
            }
        }
        throw ValidationException::withMessages([$field => 'Select a valid '.str_replace('_code', '', $field).' from the location list.']);
    }

    /** @return LocationCatalog */
    private function catalog(string $tier, ?string $country = null, ?string $state = null, ?int $stateId = null): array
    {
        $key = 'world:v1:'.hash('sha256', json_encode([config('locations.base_url'), $tier, $country, $state]));
        $cache = null;
        $stored = null;
        $lock = null;
        try {
            $cache = $this->cache();
            $stored = $cache->get($key);
            if ($this->validCachedCatalog($stored, $tier)) {
                if (now()->timestamp < $stored['at'] + config('locations.fresh_seconds')) {
                    return $this->result($stored['data'], 'cache', $stored['at']);
                }
            } else {
                $stored = null;
            }
            $lock = $cache->lock($key.':lock', 10);
            $canFetch = $lock->get() && ! $cache->has('world:cooldown');
        } catch (Throwable) {
            $canFetch = false;
            Log::warning('Location cache unavailable; using bundled catalog.');
        }
        $bundle = null;
        try {
            $bundle = match ($tier) {
                'countries' => $this->snapshots->countries(),
                'states' => $this->snapshots->states($country),
                'cities' => $this->snapshots->cities($country, $state),
            };
        } catch (LocationUnavailable) {
            // A newly added live state might not exist in the pinned snapshot yet.
        }
        try {
            if ($canFetch && ($tier !== 'cities' || $stateId !== null)) {
                try {
                    if (! $this->reserveUpstreamRequest($cache)) {
                        $canFetch = false;
                        throw new LocationUnavailable;
                    }
                    $baseUrl = rtrim(config('locations.base_url'), '/');
                    if (parse_url($baseUrl, PHP_URL_SCHEME) !== 'https' || ! parse_url($baseUrl, PHP_URL_HOST)) {
                        throw new LocationUnavailable;
                    }
                    $query = match ($tier) {
                        'countries' => ['fields' => 'iso2'],
                        'states' => ['fields' => 'country_code', 'filters' => ['country_code' => $country]],
                        'cities' => ['fields' => 'country_code,state_id,state', 'filters' => ['country_code' => $country, 'state_id' => $stateId]],
                    };
                    $response = Http::acceptJson()->withHeaders(['Accept-Language' => 'en'])
                        ->withOptions(['verify' => true, 'allow_redirects' => false])
                        ->connectTimeout(2)->timeout(5)->get($baseUrl.'/'.$tier, $query);
                    if (! $response->successful()) {
                        throw new LocationUnavailable;
                    }
                    $data = $this->normalize($response->json(), $tier, $country, $state, $stateId);
                    if ($data === [] && (($bundle !== null && $bundle !== []) || ($stored['data'] ?? []) !== [] || $tier === 'countries')) {
                        throw new LocationUnavailable;
                    }
                    if ($data === [] && $bundle === null && $stored === null) {
                        throw new LocationUnavailable;
                    }
                    $at = now()->timestamp;
                    try {
                        $cache->put($key, ['at' => $at, 'data' => $data], config('locations.stale_seconds'));
                    } catch (Throwable) {
                        Log::warning('Unable to cache location response.');
                    }

                    return $this->result($data, 'api', $at);
                } catch (Throwable) {
                    try {
                        $cache->put('world:cooldown', true, config('locations.cooldown_seconds'));
                    } catch (Throwable) {
                        // Bundled fallback does not depend on cache writes.
                    }
                    Log::warning('World location API unavailable; using fallback.', ['tier' => $tier, 'country' => $country]);
                }
            }
            if ($stored !== null && now()->timestamp < $stored['at'] + config('locations.stale_seconds')) {
                return $this->result($stored['data'], 'stale', $stored['at']);
            }
            if ($bundle !== null) {
                return $this->result($bundle, 'snapshot');
            }
            throw new LocationUnavailable;
        } finally {
            try {
                $lock?->release();
            } catch (Throwable) {
                // Cache failure cannot make a successful fallback fail.
            }
        }
    }

    private function reserveUpstreamRequest(Repository $cache): bool
    {
        $lock = $cache->lock('world:budget:lock', 5);
        if (! $lock->get()) {
            return false;
        }
        try {
            $key = 'world:budget:'.intdiv(now()->timestamp, 60);
            $cache->add($key, 0, 120);

            return $cache->increment($key) <= 60;
        } finally {
            $lock->release();
        }
    }

    private function validCachedCatalog(mixed $stored, string $tier): bool
    {
        if (! is_array($stored) || ! is_int($stored['at'] ?? null) || $stored['at'] > now()->timestamp
            || ! is_array($stored['data'] ?? null) || ! array_is_list($stored['data'])
            || ($tier === 'countries' && $stored['data'] === [])) {
            return false;
        }
        foreach ($stored['data'] as $row) {
            if (! is_array($row) || ! is_string($row['value'] ?? null) || $row['value'] === ''
                || ! is_string($row['label'] ?? null) || $row['label'] === '' || mb_strlen($row['label']) > 255
                || ($tier === 'countries' && ! preg_match('/^[A-Z]{2}$/D', $row['value']))
                || ($tier === 'states' && (! is_int($row['upstream_id'] ?? null) || $row['upstream_id'] <= 0))) {
                return false;
            }
        }

        return true;
    }

    /** @return list<LocationOption> */
    private function normalize(mixed $payload, string $tier, ?string $country, ?string $state, ?int $stateId): array
    {
        if (! is_array($payload) || ($payload['success'] ?? null) !== true || ! is_array($payload['data'] ?? null) || ! array_is_list($payload['data'])) {
            throw new LocationUnavailable;
        }
        $options = [];
        foreach ($payload['data'] as $row) {
            if (! is_array($row) || ! is_string($row['name'] ?? null) || trim($row['name']) === '' || mb_strlen($row['name']) > 255 || preg_match('/[\x00-\x1f]/', $row['name'])) {
                throw new LocationUnavailable;
            }
            $name = trim($row['name']);
            $value = $tier === 'countries' ? ($row['iso2'] ?? '') : $name;
            if ($tier === 'countries' && (! is_string($value) || ! preg_match('/^[A-Z]{2}$/D', $value))) {
                throw new LocationUnavailable;
            }
            if ($tier !== 'countries' && ($row['country_code'] ?? null) !== $country) {
                throw new LocationUnavailable;
            }
            if ($tier === 'states' && (! is_int($row['id'] ?? null) || $row['id'] <= 0)) {
                throw new LocationUnavailable;
            }
            if ($tier === 'cities' && (($row['state_id'] ?? null) !== $stateId || ($row['state']['name'] ?? null) !== $state)) {
                throw new LocationUnavailable;
            }
            $option = ['value' => $value, 'label' => $name];
            if ($tier === 'states') {
                $option['upstream_id'] = $row['id'];
            }
            if (isset($options[$value]) && $options[$value] !== $option) {
                throw new LocationUnavailable;
            }
            $options[$value] = $option;
        }
        $options = array_values($options);
        usort($options, fn ($a, $b) => strnatcasecmp($a['label'], $b['label']));

        return $options;
    }

    /**
     * @param  list<LocationOption>  $data
     * @return LocationCatalog
     */
    private function result(array $data, string $source, ?int $at = null): array
    {
        return ['data' => $data, 'meta' => ['available' => true, 'source' => $source, 'empty' => $data === [], 'fetched_at' => $at]];
    }
}
