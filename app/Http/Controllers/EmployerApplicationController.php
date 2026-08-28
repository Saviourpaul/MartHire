<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Requests\ReviewApplicationFormRequest;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Services\ApplicationFormService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployerApplicationController extends Controller
{
    public function applied(Request $request): View
    {
        return $this->candidateTable($request, null, 'Applied Candidates', 'employer.Applied-Candidates', 'employer.Applied-candidates');
    }

    public function approved(Request $request): View
    {
        return $this->candidateTable($request, ApplicationStatus::Approved, 'Approved Candidates', 'employer.Approved-Candidates', 'employer.Approved-candidates');
    }

    public function rejected(Request $request): View
    {
        return $this->candidateTable($request, ApplicationStatus::Rejected, 'Rejected Candidates', 'employer.Rejected-Candidate', 'employer.Rejected-Candidate');
    }

    public function show(Request $request, ApplicationForm $applicationForm): View
    {
        $this->ensureEmployerOwnsApplication($request, $applicationForm);

        return view('employer.application-show', [
            'application' => $applicationForm->load([
                'applicant',
                'job',
                'documents.reviewer',
                'documents.statusHistories.changedBy',
                'statusHistories.changedBy',
                'reviewer',
            ]),
        ]);
    }

    public function review(ReviewApplicationFormRequest $request, ApplicationForm $applicationForm, ApplicationFormService $service): RedirectResponse
    {
        $data = $request->validated();

        $service->reviewApplication(
            $applicationForm,
            $request->user(),
            ApplicationStatus::from($data['status']),
            $data['remarks'] ?? null
        );

        return back()->with('success', 'Application status updated.');
    }

    private function candidateTable(Request $request, ?ApplicationStatus $status, string $title, string $routeName, string $viewName): View
    {
        $applications = ApplicationForm::query()
            ->with(['job:id,title,company', 'applicant:id,first_name,last_name,email'])
            ->withCount([
                'documents',
                'documents as approved_documents_count' => fn ($query) => $query->where('status', ApplicationStatus::Approved),
            ])
            ->forEmployer($request->user())
            ->when($status, fn ($query) => $query->status($status))
            ->when($request->filled('job_id'), fn ($query) => $query->where('job_id', $request->integer('job_id')))
            ->search($request->string('search')->toString())
            ->latest('submitted_at')
            ->paginate(10)
            ->withQueryString();

        $jobs = Job::query()
            ->where('employer_id', $request->user()->id)
            ->whereHas('applications')
            ->orderBy('title')
            ->get(['id', 'title']);

        return view($viewName, [
            'applications' => $applications,
            'jobs' => $jobs,
            'statusFilter' => $status,
            'title' => $title,
            'routeName' => $routeName,
        ]);
    }

    private function ensureEmployerOwnsApplication(Request $request, ApplicationForm $application): void
    {
        $application->loadMissing('job');

        abort_unless($application->job->employer_id === $request->user()->id, 403);
    }
}
