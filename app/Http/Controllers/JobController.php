<?php

namespace App\Http\Controllers;

use App\Actions\Workable\BuildApplicationValidator;
use App\Actions\Workable\ResolveApplicationSchema;
use App\Jobs\Workable\SubmitJobApplicationJob;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function home(): View
    {
        return view('Home', ['jobs' => $this->openJobs()]);
    }

    public function dashboard(): View
    {
        return view('dashboard', ['jobs' => $this->openJobs(), 'activity' => []]);
    }

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

    public function apply(string $id): View|RedirectResponse
    {
        $posting = JobPosting::with('company')->where('shortcode', $id)->first();

        abort_if($posting === null || $posting->status !== 'open', 404);

        $job = $this->present($posting);

        if (! empty($job['apply_url'])) {
            return redirect()->away($job['apply_url']);
        }

        return view('Apply', [
            'job' => $job,
            'jobs' => $this->openJobs(),
            'id' => $id,
        ]);
    }

    /**
     * Native apply submission (design §7.2/§7.5). Validates against the posting's
     * own schema, moves uploads to the private disk, writes the application row
     * (status queued) and hands off to the outbox job — which owns the at-most-once
     * Workable POST. Employers in redirect mode, or postings we can't natively
     * support, fall back to the hosted form and never create a row here.
     */
    public function store(
        Request $request,
        string $id,
        ResolveApplicationSchema $resolve,
        BuildApplicationValidator $validator,
    ): RedirectResponse {
        $posting = JobPosting::with('company')->where('shortcode', $id)->firstOrFail();
        abort_if($posting->status !== 'open', 404);

        $company = $posting->company;
        if ($company === null || $company->apply_mode !== 'native') {
            return $this->redirectToHosted($posting, $id);
        }

        // Resolving refreshes apply_supported: if the live schema has a required
        // field we can't render/submit, don't accept a submission we'd mangle.
        $schema = $resolve($company, $posting);
        if (! $posting->apply_supported) {
            return $this->redirectToHosted($posting, $id);
        }

        $rules = $validator->rules($schema);
        $data = $request->validate($rules);

        // File inputs are any rule that declares 'file' (resume + file questions).
        // They go to the private disk; only path+name ride in the payload.
        $fileFields = array_keys(array_filter(
            $rules,
            fn ($rule) => is_array($rule) && in_array('file', $rule, true),
        ));

        $files = [];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $upload = $request->file($field);
                $path = $upload->store('applications/'.$posting->id, config('workable.application.disk'));
                $files[$field] = ['path' => $path, 'name' => $upload->getClientOriginalName()];
            }
            unset($data[$field]); // never store the uploaded file inside the data payload
        }

        $application = JobApplication::create([
            'job_posting_id' => $posting->id,
            'idempotency_key' => (string) Str::uuid(),
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'status' => 'queued',
            'payload' => ['data' => $data, 'files' => $files],
            'consent_version' => config('workable.application.consent_version'),
            'consented_at' => now(),
        ]);

        SubmitJobApplicationJob::dispatch($application);

        return redirect()->route('jobs.show', $id)->with('status', 'application-received');
    }

    /** Fall back to the employer's hosted Workable form (or the posting page if none). */
    private function redirectToHosted(JobPosting $posting, string $id): RedirectResponse
    {
        $hosted = $posting->application_url ?: ($posting->shortlink ?: $posting->url);

        return $hosted ? redirect()->away($hosted) : redirect()->route('jobs.show', $id);
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
