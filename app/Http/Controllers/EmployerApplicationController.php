<?php

namespace App\Http\Controllers;

use App\Enums\CandidatePipelineStage;
use App\Http\Requests\MoveCandidatePipelineRequest;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Services\ApplicationFormService;
use App\Support\OrganizationPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EmployerApplicationController extends Controller
{
    public function applied(Request $request): View
    {
        $stage = $request->filled('stage') ? CandidatePipelineStage::tryFrom($request->string('stage')->toString()) : null;

        abort_if($request->filled('stage') && ! $stage, 404);

        return $this->candidateTable($request, $stage, 'Candidate Pipeline', 'employer.Applied-Candidates', 'employer.Applied-candidates');
    }

    public function show(Request $request, ApplicationForm $applicationForm): View
    {
        $this->ensureEmployerOwnsApplication($request, $applicationForm);

        return view('employer.application-show', [
            'allowedStages' => array_values(array_filter($applicationForm->status->allowedNextStages(),
                fn (CandidatePipelineStage $stage): bool => Gate::allows('managePipeline', $applicationForm)
                    && ($stage !== CandidatePipelineStage::Selected || Gate::allows('select', $applicationForm)))),
            'application' => $applicationForm->load([
                'applicant',
                'job',
                'statusHistories.changedBy',
                'reviewer',
            ]),
        ]);
    }

    public function movePipeline(MoveCandidatePipelineRequest $request, ApplicationForm $applicationForm, ApplicationFormService $service): RedirectResponse
    {
        $data = $request->validated();

        $application = $service->moveCandidate(
            $applicationForm,
            $request->user(),
            CandidatePipelineStage::from($data['stage']),
            $data['remarks'] ?? null
        );

        return back()->with('success', "Candidate moved to {$application->status->label()}.");
    }

    private function candidateTable(Request $request, ?CandidatePipelineStage $status, string $title, string $routeName, string $viewName): View
    {
        $applications = ApplicationForm::query()
            ->with(['job:id,title,company', 'applicant:id,first_name,last_name,email'])
            ->withCount('documents')
            ->forEmployer($request->user())
            ->when($status, fn ($query) => $query->status($status))
            ->when($request->filled('job_id'), fn ($query) => $query->where('job_id', $request->integer('job_id')))
            ->search($request->string('search')->toString())
            ->latest('submitted_at')
            ->paginate(10)
            ->withQueryString();

        $jobs = Job::query()
            ->tap(fn ($query) => app(OrganizationPermissions::class)->scopeJobs($query, $request->user()))
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
        Gate::authorize('viewForEmployer', $application);
    }
}
