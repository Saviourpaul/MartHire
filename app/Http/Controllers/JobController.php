<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public job board pages (design W2). Reads only from our database — never
 * calls Workable during a page view. Search/filter is client-side (Alpine);
 * the controller passes the full set of open postings plus the categories
 * derived from their departments. Apply is redirect-only here (native apply
 * is W3): the Apply button links to the posting's hosted Workable URL.
 */
class JobController extends Controller
{
    public function index(): View
    {
        $jobs = $this->openJobs();

        $categories = $jobs
            ->pluck('field')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $name) => ['name' => $name, 'icon' => 'briefcase', 'blurb' => ''])
            ->all();

        return view('Jobs', ['jobs' => $jobs, 'categories' => $categories]);
    }

    public function show(string $id): View
    {
        $posting = JobPosting::with('company')->where('shortcode', $id)->first();

        abort_if($posting === null, 404);

        return view('JobDetail', [
            'job' => $this->present($posting),
            'jobs' => $this->openJobs(),   // for the "similar roles" section
            'id' => $id,
        ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function openJobs(): Collection
    {
        return JobPosting::with('company')
            ->where('status', 'open')
            ->orderByDesc('external_updated_at')
            ->get()
            ->map(fn (JobPosting $p) => $this->present($p))
            ->values();
    }

    /** @return array<string, mixed> */
    private function present(JobPosting $posting): array
    {
        $location = $posting->location ?? [];
        $locationStr = $location['location_str']
            ?? (collect([$location['city'] ?? null, $location['country'] ?? null])
                ->filter()->implode(', ') ?: 'Location not specified');

        $workplace = match ($posting->workplace_type) {
            'remote' => 'Remote',
            'hybrid' => 'Hybrid',
            default => 'On-site',
        };

        $salary = null;
        $s = $posting->salary;
        if (is_array($s) && ! empty($s['salary_from'])) {
            $currency = $s['salary_currency'] ?? '';
            $salary = trim($currency.' '.number_format((float) $s['salary_from']))
                .(! empty($s['salary_to']) ? ' – '.number_format((float) $s['salary_to']) : '');
        }

        // Sanitise once; derive the plain-text summary from the CLEANED html so
        // stripped <script>/<style> text never leaks into the summary.
        $clean = HtmlSanitizer::clean($posting->content_html);
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($clean)));
        $summary = $plain !== '' ? Str::limit($plain, 160) : ($posting->full_title ?? $posting->title);

        return [
            'id' => $posting->shortcode,
            'title' => $posting->title,
            'company' => $posting->company?->name ?? 'Employer',
            'location' => $locationStr,
            'workplace' => $workplace,
            // Workable's list payload carries no standard employment type; default
            // to Full-time (W2 limitation — refine when a type field is available).
            'type' => 'Full-time',
            'salary' => $salary,
            'posted_at' => optional($posting->external_updated_at)->toIso8601String() ?? now()->toIso8601String(),
            'field' => $posting->department ?: 'Other',
            'tags' => array_values($posting->keywords ?? []),
            'summary' => $summary,
            'content_html' => $clean,
            'apply_url' => $posting->application_url ?: ($posting->shortlink ?: $posting->url),
            'closed' => $posting->status === 'closed',
        ];
    }
}
