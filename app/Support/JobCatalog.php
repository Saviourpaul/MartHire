<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Prepares job listings for the public pages (home, jobs, job detail).
 *
 * Pass real jobs in when the live feed exists (see resources/data/sample-jobs.php for the expected keys);
 * until then the sample listings are used. Categories come from the jobs themselves, so an empty
 * category never shows. Known names pick up an icon from resources/data/job-categories.php, and
 * places are matched against resources/data/places.php to put jobs on the hero map.
 */
class JobCatalog
{
    /** @var Collection<int, array<string, mixed>> */
    public Collection $jobs;

    /** @var Collection<int, array{name: string, slug: string, icon: string, blurb: string, count: int}> */
    public Collection $categories;

    /** @var array{cities: array<string, array>, countries: array<string, array>, aliases: array<string, string>}|null */
    private ?array $places = null;

    /** @var array<string, string>|null */
    private ?array $countryLookup = null;

    /**
     * @param  iterable<array<string, mixed>>  $jobs
     * @param  iterable<array<string, string>>  $knownCategories
     */
    public function __construct(iterable $jobs, iterable $knownCategories = [])
    {
        $this->jobs = collect($jobs)
            ->sortByDesc(fn (array $job) => strtotime($job['posted_at'] ?? '') ?: 0)
            ->values();

        $known = collect($knownCategories)->keyBy(fn (array $category) => Str::slug($category['name']));

        $this->categories = $this->jobs
            ->filter(fn (array $job) => ! empty($job['field']))
            ->groupBy(fn (array $job) => Str::slug($job['field']))
            ->map(fn (Collection $group, string $slug) => [
                'name' => $known->get($slug)['name'] ?? $group->first()['field'],
                'slug' => $slug,
                'icon' => $known->get($slug)['icon'] ?? 'briefcase',
                'blurb' => $known->get($slug)['blurb'] ?? '',
                'count' => $group->count(),
            ])
            ->sortBy([['count', 'desc'], ['name', 'asc']])
            ->values();
    }

    /**
     * Uses the given jobs, or the sample listings when none are given.
     *
     * @param  iterable<array<string, mixed>>|null  $jobs
     */
    public static function make(?iterable $jobs = null): self
    {
        return new self(
            $jobs ?? require resource_path('data/sample-jobs.php'),
            require resource_path('data/job-categories.php'),
        );
    }

    /**
     * How many jobs have each value of a field (for example `type` or `workplace`), most familiar values first.
     *
     * @param  list<string>  $preferredOrder
     * @return Collection<string, int>
     */
    public function counts(string $key, array $preferredOrder = []): Collection
    {
        $rank = fn (string $value): int => ($position = array_search($value, $preferredOrder, true)) === false
            ? count($preferredOrder)
            : $position;

        return $this->jobs->pluck($key)->filter()->countBy()
            ->sortBy(fn (int $count, string $value) => sprintf('%03d%s', $rank($value), $value));
    }

    /**
     * What the browser needs to search, filter and sort the cards. Same order as `$jobs`.
     *
     * @return list<array<string, mixed>>
     */
    public function searchIndex(): array
    {
        return $this->jobs->map(fn (array $job) => [
            'text' => mb_strtolower(implode(' ', [
                $job['title'] ?? '',
                $job['company'] ?? '',
                $job['field'] ?? '',
                $job['summary'] ?? '',
                implode(' ', $job['tags'] ?? []),
            ])),
            'where' => mb_strtolower(implode(' ', array_filter([
                $job['location'] ?? '',
                $job['workplace'] ?? '',
                $this->placeOf($job)['country'] ?? '',
            ]))),
            'workplace' => $job['workplace'] ?? null,
            'type' => $job['type'] ?? null,
            'category' => Str::slug($job['field'] ?? ''),
            'title' => $job['title'] ?? '',
            'posted' => (strtotime($job['posted_at'] ?? '') ?: 0) * 1000,
        ])->all();
    }

    /**
     * One marker per country that has jobs, for the hero map, busiest first.
     *
     * @return Collection<int, array{code: string, country: string, lat: float, lon: float, count: int, cities: list<string>}>
     */
    public function pins(): Collection
    {
        return $this->jobs
            ->map(fn (array $job) => $this->placeOf($job))
            ->filter()
            ->groupBy('code')
            ->map(function (Collection $group) {
                $cityCounts = $group->pluck('city')->filter()->countBy()->sortDesc();
                $anchor = $cityCounts->isEmpty() ? $group->first() : $group->firstWhere('city', $cityCounts->keys()->first());

                return [
                    'code' => strtolower($anchor['code']),
                    'country' => $anchor['country'],
                    'lat' => $anchor['lat'],
                    'lon' => $anchor['lon'],
                    'count' => $group->count(),
                    'cities' => $cityCounts->keys()->take(3)->values()->all(),
                ];
            })
            ->sortBy([['count', 'desc'], ['country', 'asc']])
            ->values();
    }

    /**
     * Works out where a job is: the city when it is known, otherwise the country.
     * Returns null for places that cannot be pinned, such as "Remote, worldwide".
     *
     * @param  array<string, mixed>  $job
     * @return array{code: string, country: string, city: ?string, lat: float, lon: float}|null
     */
    public function placeOf(array $job): ?array
    {
        $places = $this->places();
        $parts = collect(explode(',', ($job['city'] ?? '').','.($job['location'] ?? '')))
            ->map(fn (string $part) => trim($part))
            ->filter()
            ->values();

        foreach ($parts as $part) {
            $city = $places['cities'][$this->normalise($part)] ?? null;

            if ($city) {
                [$code, $lat, $lon] = $city;

                return ['code' => $code, 'country' => $places['countries'][$code][0] ?? $code, 'city' => $part, 'lat' => $lat, 'lon' => $lon];
            }
        }

        $candidates = $parts->reverse()->prepend($job['country_code'] ?? $job['country'] ?? null)->filter();

        foreach ($candidates as $candidate) {
            $code = $this->countryLookup()[$this->normalise((string) $candidate)] ?? null;

            if ($code) {
                [$name, $lat, $lon] = $places['countries'][$code];

                return ['code' => $code, 'country' => $name, 'city' => null, 'lat' => $lat, 'lon' => $lon];
            }
        }

        return null;
    }

    private function normalise(string $value): string
    {
        return Str::of($value)->ascii()->lower()->trim()->toString();
    }

    /** @return array{cities: array<string, array>, countries: array<string, array>, aliases: array<string, string>} */
    private function places(): array
    {
        return $this->places ??= require resource_path('data/places.php');
    }

    /** @return array<string, string> lower-case name, code or alias => country code */
    private function countryLookup(): array
    {
        if ($this->countryLookup === null) {
            $places = $this->places();
            $this->countryLookup = $places['aliases'];

            foreach ($places['countries'] as $code => [$name]) {
                $this->countryLookup[strtolower($code)] = $code;
                $this->countryLookup[$this->normalise($name)] = $code;
            }
        }

        return $this->countryLookup;
    }
}
