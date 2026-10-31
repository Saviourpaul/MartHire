<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationFormRequest;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Services\ApplicationFormService;
use App\Services\LocationService;
use App\Services\PublicJobService;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class JobApplicationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $applications = $request->user()
            ->applications()
            ->with(['job', 'documents'])
            ->latest('submitted_at')
            ->paginate(10)
            ->withQueryString();

        return view('client.jobs', [
            'applications' => $applications,
        ]);
    }

    public function create(Request $request, Job $job): View|RedirectResponse
    {
        abort_unless($job->isAcceptingApplications(), 404);

        $user = $request->user();

        if (! $user) {
            $request->session()->put('url.intended', route('applications.create', $job));

            return redirect()
                ->route('login')
                ->with('info', 'Log in or Register to continue with job application.');
        }

        abort_unless($user->isApplicant(), 403);

        if (! $user->hasVerifiedEmail()) {
            $request->session()->put('url.intended', route('applications.create', $job));

            return redirect()->route('verification.notice');
        }

        $existingApplication = $user
            ->applications()
            ->where('job_id', $job->id)
            ->first();

        if ($existingApplication) {
            return redirect()
                ->route('client.applications.show', $existingApplication)
                ->with('info', 'You have already applied for this job.');
        }

        $locations = app(LocationService::class);
        $countryCode = $request->old('country_code', $user->country_code ?? '');
        $state = $request->old('state', $user->state ?? '');

        return view('client.job-application', [
            'job' => $job,
            'publicJob' => app(PublicJobService::class)->native($job),
            'user' => $user,
            'countries' => $locations->countries(),
            'states' => is_string($countryCode) ? $locations->states($countryCode) : [],
            'cities' => is_string($countryCode) && is_string($state) ? $locations->cities($countryCode, $state) : [],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreApplicationFormRequest $request, Job $job, ApplicationFormService $service): RedirectResponse|JsonResponse
    {
        // Applicants have no active organization. Bind every submission and its
        // documents to the job's organization while the service writes them.
        $application = app(OrganizationContext::class)->runInOrganization(
            $job->organization,
            fn () => $service->submit($job, $request->user(), $request->validated())
        );

        if ($request->expectsJson()) {
            $request->session()->flash('success', 'Your application has been submitted successfully.');

            return response()->json([
                'submitted' => true,
                'message' => 'Your application has been submitted successfully.',
                'redirect' => route('client.applications.show', $application),
            ], 201);
        }

        return redirect()
            ->route('client.applications.show', $application)
            ->with('success', 'Your application has been submitted successfully.');
    }

    public function show(Request $request, ApplicationForm $applicationForm): View
    {
        $this->authorize('view', $applicationForm);

        return view('client.application-show', [
            'application' => $applicationForm->load(['job', 'applicant', 'documents', 'statusHistories.changedBy', 'interviews']),
            'publicJob' => $applicationForm->job ? app(PublicJobService::class)->native($applicationForm->job) : null,
        ]);
    }

    public function documents(Request $request): View
    {
        $documents = ApplicationDocument::query()
            ->with('applicationForm.job')
            ->whereHas('applicationForm', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('client.documents', [
            'documents' => $documents,
        ]);
    }

    public function notifications(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('client.notifications', [
            'notifications' => $notifications,
        ]);
    }
}
