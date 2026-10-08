<?php

namespace App\Services;

use App\Exceptions\LocationUnavailable;
use Throwable;

class LocationSnapshots
{
    private ?array $manifest = null;

    private array $countries = [];

    public function countries(): array
    {
        return $this->options($this->read('countries.json', $this->manifest()['countries_sha256']), true);
    }

    public function states(string $country): array
    {
        return $this->country($country)['states'];
    }

    public function cities(string $country, string $state): array
    {
        $entry = $this->country($country)['cities'][$state] ?? null;
        if (! is_array($entry) || ! preg_match('#^cities/'.$country.'/[a-f0-9]{64}\.json$#D', $entry['file'] ?? '')
            || ! is_string($entry['sha256'] ?? null)) {
            throw new LocationUnavailable;
        }

        return $this->options($this->read($entry['file'], $entry['sha256']));
    }

    private function country(string $code): array
    {
        if (! preg_match('/^[A-Z]{2}$/D', $code) || ! isset($this->manifest()['country_files'][$code])) {
            throw new LocationUnavailable;
        }
        if (! isset($this->countries[$code])) {
            $data = $this->read('countries/'.$code.'.json', $this->manifest()['country_files'][$code]);
            if (! is_array($data['states'] ?? null) || ! is_array($data['cities'] ?? null)) {
                throw new LocationUnavailable;
            }
            $data['states'] = $this->options($data['states']);
            $this->countries[$code] = $data;
        }

        return $this->countries[$code];
    }

    private function manifest(): array
    {
        if ($this->manifest === null) {
            $manifest = $this->read('manifest.json');
            if (($manifest['schema'] ?? null) !== 1 || ! is_string($manifest['countries_sha256'] ?? null)
                || ! is_array($manifest['country_files'] ?? null)) {
                throw new LocationUnavailable;
            }
            foreach ([$manifest['countries_sha256'], ...array_values($manifest['country_files'])] as $hash) {
                if (! is_string($hash) || ! preg_match('/^[a-f0-9]{64}$/D', $hash)) {
                    throw new LocationUnavailable;
                }
            }
            $this->manifest = $manifest;
        }

        return $this->manifest;
    }

    private function read(string $file, ?string $checksum = null): array
    {
        try {
            $path = config('locations.snapshot_path').'/'.$file;
            if (! is_file($path) || ! is_readable($path)) {
                throw new LocationUnavailable;
            }
            $json = file_get_contents($path);
            if ($json === false || ($checksum !== null && ! hash_equals($checksum, hash('sha256', $json)))) {
                throw new LocationUnavailable;
            }
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($data)) {
                throw new LocationUnavailable;
            }

            return $data;
        } catch (Throwable) {
            throw new LocationUnavailable;
        }
    }

    private function options(array $options, bool $countries = false): array
    {
        if (! array_is_list($options) || ($countries && $options === [])) {
            throw new LocationUnavailable;
        }
        foreach ($options as $option) {
            if (! is_array($option) || ! is_string($option['value'] ?? null) || $option['value'] === ''
                || ! is_string($option['label'] ?? null) || $option['label'] === '' || mb_strlen($option['label']) > 255
                || ($countries && ! preg_match('/^[A-Z]{2}$/D', $option['value']))) {
                throw new LocationUnavailable;
            }
        }

        return $options;
    }
}
