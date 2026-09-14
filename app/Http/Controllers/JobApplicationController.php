<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationFormRequest;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\NigeriaState;
use App\Services\ApplicationFormService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class JobApplicationController extends Controller
{
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
                ->with('info', 'Log in with an applicant account to continue your job application.');
        }

        abort_unless($user->isApplicant(), 403);

        $existingApplication = $user
            ->applications()
            ->where('job_id', $job->id)
            ->first();

        if ($existingApplication) {
            return redirect()
                ->route('client.applications.show', $existingApplication)
                ->with('info', 'You have already applied for this job.');
        }

        return view('client.Application', [
            'job' => $job,
            'user' => $user,
            'states' => NigeriaState::query()
                ->ordered()
                ->get(),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreApplicationFormRequest $request, Job $job, ApplicationFormService $service): RedirectResponse
    {
        $application = $service->submit($job, $request->user(), $request->validated());

        return redirect()
            ->route('client.applications.show', $application)
            ->with('success', 'Your application has been submitted successfully.');
    }

    public function show(Request $request, ApplicationForm $applicationForm): View
    {
        abort_unless($applicationForm->user_id === $request->user()->id, 403);

        return view('client.application-show', [
            'application' => $applicationForm->load(['job', 'documents.statusHistories.changedBy', 'statusHistories.changedBy']),
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
