<?php

namespace App\Http\Controllers;

use App\Actions\Workable\BuildApplicationValidator;
use App\Actions\Workable\ResolveApplicationSchema;
use App\Integrations\Workable\DTO\ApplicationSchema;
use App\Integrations\Workable\DTO\QuestionData;
use App\Jobs\Workable\SubmitJobApplicationJob;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
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

    public function apply(string $id, ResolveApplicationSchema $resolve): View|RedirectResponse
    {
        $posting = JobPosting::with('company')->where('shortcode', $id)->first();

        abort_if($posting === null || $posting->status !== 'open', 404);

        $company = $posting->company;
        $job = $this->present($posting);

        // Native apply only when the employer opts in AND we can render the live
        // schema; the form is fed the posting's real fields/questions. Everyone
        // else continues to the employer's hosted form.
        if ($company !== null && $company->apply_mode === 'native') {
            $schema = $resolve($company, $posting);
            if ($posting->apply_supported) {
                return view('Apply', [
                    'job' => $job,
                    'jobs' => $this->openJobs(),
                    'id' => $id,
                    'form' => $this->presentApplicationForm($schema),
                ]);
            }
        }

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
        $rules['confirm'] = ['accepted']; // the blade's consent box must be ticked

        // The native form posts the blade's own field names; normalise them to the
        // canonical schema keys (first_name, question_{id}, resume) before validating.
        [$input, $fileInputs] = $this->normalizeApplyInput($request, $schema);
        $validated = Validator::make($input + $fileInputs, $rules)->validate();

        // File answers go to the PRIVATE disk; only path+name ride in the payload.
        $fileKeys = array_keys(array_filter(
            $rules,
            fn ($rule) => is_array($rule) && in_array('file', $rule, true),
        ));
        $files = [];
        foreach ($fileKeys as $key) {
            if (isset($fileInputs[$key])) {
                $upload = $fileInputs[$key];
                $path = $upload->store('applications/'.$posting->id, config('workable.application.disk'));
                $files[$key] = ['path' => $path, 'name' => $upload->getClientOriginalName()];
            }
            unset($validated[$key]); // the uploaded file never lives in the data payload
        }
        unset($validated['confirm']); // consent is recorded below, never sent to Workable

        $application = JobApplication::create([
            'job_posting_id' => $posting->id,
            'idempotency_key' => (string) Str::uuid(),
            'email' => $validated['email'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'status' => 'queued',
            'payload' => ['data' => $validated, 'files' => $files],
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

    /**
     * Map the native form's posted names to the canonical schema keys the
     * validator/payload-builder expect (firstname→first_name, answers[id]→
     * question_{id}, answer_files[id]→question_{id}). The schema drives which
     * questions exist, so this never trusts arbitrary posted keys.
     *
     * @return array{0: array<string, mixed>, 1: array<string, \Illuminate\Http\UploadedFile>}
     */
    private function normalizeApplyInput(Request $request, ApplicationSchema $schema): array
    {
        $scalars = [
            'first_name' => $request->input('firstname'),
            'last_name' => $request->input('lastname'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'cover_letter' => $request->input('cover_letter'),
            'summary' => $request->input('summary'),
            'confirm' => $request->input('confirm'),
        ];

        $files = [];
        if ($request->hasFile('resume')) {
            $files['resume'] = $request->file('resume');
        }

        foreach ($schema->questions as $question) {
            /** @var QuestionData $question */
            $key = 'question_'.$question->id;
            if ($question->type === 'file') {
                if ($request->hasFile('answer_files.'.$question->id)) {
                    $files[$key] = $request->file('answer_files.'.$question->id);
                }

                continue;
            }
            $scalars[$key] = $request->input('answers.'.$question->id);
        }

        return [$scalars, $files];
    }

    /**
     * Shape a live Workable schema into the data the Apply view expects — the
     * same keys as resources/data/sample-application-form.php, so the blade is
     * fed real fields/questions without any change to the view.
     *
     * @return array{fields: array<int, array<string, mixed>>, questions: array<int, array<string, mixed>>}
     */
    private function presentApplicationForm(ApplicationSchema $schema): array
    {
        $resume = collect($schema->formFields)->firstWhere('key', 'resume') ?? [];
        $hasSummary = collect($schema->formFields)->contains(fn ($f) => ($f['key'] ?? null) === 'summary');

        $fields = [
            ['key' => 'firstname', 'label' => __('First name'), 'type' => 'text', 'required' => true, 'section' => 'details', 'autocomplete' => 'given-name'],
            ['key' => 'lastname', 'label' => __('Last name'), 'type' => 'text', 'required' => true, 'section' => 'details', 'autocomplete' => 'family-name'],
            ['key' => 'email', 'label' => __('Email address'), 'type' => 'email', 'required' => true, 'section' => 'details', 'autocomplete' => 'email'],
            ['key' => 'phone', 'label' => __('Phone number'), 'type' => 'tel', 'required' => false, 'section' => 'details', 'autocomplete' => 'tel'],
            ['key' => 'resume', 'label' => __('CV or résumé'), 'type' => 'file', 'required' => (bool) ($resume['required'] ?? true), 'section' => 'documents', 'accept' => $resume['supported_file_types'] ?? ['pdf', 'doc', 'docx', 'rtf'], 'max_file_size' => $resume['max_file_size'] ?? (5 * 1024 * 1024)],
            ['key' => 'cover_letter', 'label' => __('Cover letter'), 'type' => 'textarea', 'required' => false, 'section' => 'documents'],
        ];

        if ($hasSummary) {
            $fields[] = ['key' => 'summary', 'label' => __('Summary'), 'type' => 'textarea', 'required' => false, 'section' => 'documents'];
        }

        $questions = array_map(fn (QuestionData $q) => [
            'id' => $q->id,
            'body' => $q->label,
            'type' => $q->type,
            'required' => $q->required,
            'single_answer' => $q->singleAnswer ?? true,
            'choices' => $q->choices,
            'supported_file_types' => $q->supportedFileTypes,
            'max_file_size' => $q->maxFileSize,
        ], $schema->questions);

        return ['fields' => $fields, 'questions' => $questions];
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
