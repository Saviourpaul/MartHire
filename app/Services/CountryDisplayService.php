<?php

namespace App\Services;

use Illuminate\Support\Str;
use Nnjeim\World\CountryFlag;
use Nnjeim\World\Models\Country;
use ReflectionClass;
use Throwable;

/** Read-only country presentation; never expands the set accepted by validation. */
class CountryDisplayService
{
    private ?array $reference = null;

    public function __construct(private JobCountryService $countries) {}

    /** @return array{code: ?string, name: string, flag_url: ?string, flag_emoji: string} */
    public function present(?string $code = null, ?string $name = null): array
    {
        $code = trim($code ?? '');
        $name = trim($name ?? '');
        $reference = $this->reference();
        $match = null;
        foreach ([$code, $name] as $value) {
            if ($value === '') {
                continue;
            }
            $resolved = $reference['aliases'][$this->normalise($value)] ?? null;
            $match = $resolved ? ($reference['countries'][$resolved] ?? null) : null;
            if ($match) {
                break;
            }
        }

        if (! $match) {
            return ['code' => null, 'name' => $name !== '' ? $name : __('Country not provided'), 'flag_url' => null, 'flag_emoji' => ''];
        }

        return [
            'code' => $match['code'], 'name' => $match['name'],
            'flag_url' => $this->countries->flagUrl($match['code']),
            'flag_emoji' => CountryFlag::emoji($match['code']) ?? '',
        ];
    }

    private function normalise(string $value): string
    {
        return Str::lower(Str::ascii(trim($value)));
    }

    private function reference(): array
    {
        if ($this->reference !== null) {
            return $this->reference;
        }
        $reference = ['countries' => [], 'aliases' => []];
        try {
            // World's bundled names permit legacy country display even when its
            // reference database is temporarily unavailable. These are not options.
            $path = dirname((new ReflectionClass(Country::class))->getFileName(), 3).'/resources/json/countries.json';
            foreach (json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) as $country) {
                $iso2 = strtoupper(trim((string) ($country['iso2'] ?? '')));
                $countryName = trim((string) ($country['name'] ?? ''));
                if (! preg_match('/^[A-Z]{2}$/D', $iso2) || $countryName === '') {
                    continue;
                }
                $reference['countries'][$iso2] = ['code' => $iso2, 'name' => $countryName];
                foreach ([$iso2, $countryName, $country['iso3'] ?? ''] as $alias) {
                    if (is_string($alias) && $alias !== '') {
                        $reference['aliases'][$this->normalise($alias)] = $iso2;
                    }
                }
            }
        } catch (Throwable) {
            // Installed World rows below remain the first source of truth.
        }
        foreach ($this->countries->all() as $iso2 => $country) {
            $reference['countries'][$iso2] = ['code' => $iso2, 'name' => $country['name']];
            $reference['aliases'][$this->normalise($iso2)] = $iso2;
            $reference['aliases'][$this->normalise($country['name'])] = $iso2;
        }
        // The legacy hero-map reference is optional; country display must not
        // fail when that separate presentation file is absent.
        $path = resource_path('data/places.php');
        $places = is_file($path) ? require $path : [];
        $aliases = ($places['aliases'] ?? []) + ['uk' => 'GB', 'u.k.' => 'GB', 'usa' => 'US', 'u.s.a.' => 'US'];
        foreach ($aliases as $alias => $iso2) {
            if (isset($reference['countries'][$iso2])) {
                $reference['aliases'][$this->normalise($alias)] = $iso2;
            }
        }

        return $this->reference = $reference;
    }
}
