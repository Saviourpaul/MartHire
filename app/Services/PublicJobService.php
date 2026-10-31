<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobPosting;
use App\Models\Scopes\OrganizationScope;
use App\Support\HtmlSanitizer;
use App\Support\JobCatalog;
use App\Support\JobDescription;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nnjeim\World\CountryFlag;

/** One public presentation contract for platform jobs and imported Workable jobs. */
class PublicJobService
{
    public function __construct(private JobCountryService $countries) {}

    /** @return Collection<int, array<string, mixed>> */
    public function open(): Collection
    {
        // Public discovery intentionally crosses organizations, but only exposes
        // published jobs whose application deadline has not passed.
        $native = Job::withoutGlobalScope(OrganizationScope::class)
            ->acceptingApplications()
            ->latest('created_at')
            ->get()
            ->map(fn (Job $job): array => $this->native($job));

        $workable = JobPosting::with('company')->where('status', 'open')
            ->orderByDesc('external_updated_at')->get()
            ->map(fn (JobPosting $posting): array => $this->workable($posting));

        return $native->concat($workable)
            ->sortByDesc('posted_at')->values();
    }

    /** @return array<string, mixed> */
    public function native(Job $job): array
    {
        $content = app(JobDescription::class)->toHtml($job->description);
        // Country is the native job's only location. Retain legacy text in the
        // database, using it only to recognize countries on older records.
        $country = $this->countryData($job->country_code, $job->country_code ? null : $job->location);
        $legacyLocation = empty($country['country']) && ! $job->country_code ? $job->location : null;

        return [
            // Workable shortcodes and native IDs may coincide; public keys must not.
            'id' => 'native:'.$job->id,
            'source' => 'native',
            'title' => $job->title,
            'company' => $job->company,
            'logo_url' => $job->logoUrl(),
            'location' => $legacyLocation,
            'type' => $job->employmentTypeLabel(),
            'workplace' => null,
            'salary' => null,
            'posted_at' => $job->created_at?->toIso8601String(),
            'expires_at' => $job->due_date?->endOfDay()->toIso8601String(),
            'field' => $job->category ?: 'Other',
            'tags' => [],
            'summary' => $this->summary($content),
            'content_html' => $content,
            'detail_url' => route('jobs.show', $job),
            'apply_url' => route('applications.create', $job),
            'closed' => ! $job->isAcceptingApplications(),
            ...$country,
        ];
    }

    /** @return array<string, mixed> */
    public function workable(JobPosting $posting): array
    {
        $location = $posting->location ?? [];
        $locationString = $location['location_str']
            ?? (collect([$location['city'] ?? null, $location['country'] ?? null])
                ->filter()->implode(', ') ?: 'Location not specified');
        $salary = $posting->salary;
        $salaryText = null;
        if (is_array($salary) && ! empty($salary['salary_from'])) {
            $salaryText = trim(($salary['salary_currency'] ?? '').' '.number_format((float) $salary['salary_from']))
                .(! empty($salary['salary_to']) ? ' – '.number_format((float) $salary['salary_to']) : '');
        }
        $content = HtmlSanitizer::clean($posting->content_html);

        return [
            'id' => $posting->shortcode,
            'source' => 'workable',
            'title' => $posting->title,
            'company' => $posting->company?->name ?? 'Employer',
            'location' => $locationString,
            'city' => $location['city'] ?? null,
            'workplace' => match ($posting->workplace_type) {
                'remote' => 'Remote', 'hybrid' => 'Hybrid', default => 'On-site',
            },
            'type' => 'Full-time',
            'salary' => $salaryText,
            'posted_at' => $posting->external_updated_at?->toIso8601String() ?? $posting->created_at?->toIso8601String(),
            'field' => $posting->department ?: 'Other',
            'tags' => array_values($posting->keywords ?? []),
            'summary' => $this->summary($content) ?: ($posting->full_title ?? $posting->title),
            'content_html' => $content,
            'detail_url' => route('workable-jobs.show', $posting->shortcode),
            'apply_url' => $posting->application_url ?: ($posting->shortlink ?: $posting->url),
            'closed' => $posting->status !== 'open',
            ...$this->countryData($location['country_code'] ?? $location['country'] ?? null, $locationString, $location['city'] ?? null),
        ];
    }

    /** @return array<string, mixed> */
    private function countryData(?string $value, ?string $location, ?string $city = null): array
    {
        $country = $this->countries->find($value);
        if ($country === null) {
            foreach (array_reverse(explode(',', $location ?? '')) as $part) {
                if ($country = $this->countries->find(trim($part))) {
                    break;
                }
            }
        }
        // Keep imported/legacy location matching working if World has not been
        // installed yet. New organization jobs still require a World country.
        if ($country === null) {
            $place = (new JobCatalog([]))->placeOf(['country_code' => $value, 'location' => $location, 'city' => $city]);
            if ($place !== null) {
                $code = strtoupper($place['code']);
                $country = $this->countries->find($code) ?? [
                    'code' => $code, 'name' => $place['country'],
                    'latitude' => $place['lat'], 'longitude' => $place['lon'],
                    'flag_url' => $this->countries->flagUrl($code),
                    'flag_emoji' => CountryFlag::emoji($code) ?? '',
                ];
            }
        }

        return [
            'country_code' => $country['code'] ?? null,
            'country' => $country['name'] ?? null,
            'location_label' => $country !== null ? '' : ($location ?? ''),
            'latitude' => $country['latitude'] ?? null,
            'longitude' => $country['longitude'] ?? null,
            'flag_url' => $country['flag_url'] ?? null,
            'flag_emoji' => $country['flag_emoji'] ?? null,
        ];
    }

    private function summary(string $html): string
    {
        $text = preg_replace('/<[^>]+>/', ' ', $html);

        return Str::limit(trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'))), 160);
    }
}
