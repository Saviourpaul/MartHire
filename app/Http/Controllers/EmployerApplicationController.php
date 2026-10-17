<?php

namespace App\Http\Controllers;

use App\Enums\CandidatePipelineStage;
use App\Http\Requests\MoveCandidatePipelineRequest;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Services\ApplicationFormService;
use App\Services\EmailTemplateService;
use App\Support\OrganizationPermissions;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        $canSendEmail = Gate::allows('send_candidate_email', $applicationForm);
        $relations = ['applicant', 'job', 'statusHistories.changedBy', 'reviewer',
            'interviews' => fn (HasMany $query): HasMany => $query->latest('start_at')];

        if (Gate::allows('viewDocuments', $applicationForm)) {
            $relations[] = 'documents';
        }

        if ($canSendEmail) {
            $relations['interviews.messages'] = fn (HasMany $query): HasMany => $query->select([
                'id', 'organization_id', 'application_form_id', 'interview_id', 'interview_version', 'type',
                'sent_by', 'rendered_subject', 'status', 'queued_at', 'sent_at', 'created_at', 'error_message',
            ])->latest('id');
            $relations[] = 'interviews.messages.sentBy';
        }

        $applicationForm->load($relations);

        if ($canSendEmail) {
            foreach ($applicationForm->interviews as $interview) {
                $interview->setRelation('applicationForm', $applicationForm);

                foreach ($interview->messages as $message) {
                    $message->setRelation('applicationForm', $applicationForm)->setRelation('interview', $interview);
                }
            }
        }

        $templateDefaults = $canSendEmail
            ? app(EmailTemplateService::class)->forSending($applicationForm->organization_id)
                ->mapWithKeys(fn ($template): array => [$template->type->value => ['subject' => $template->subject, 'body' => $template->body]])->all()
            : [];

        return view('employer.application-show', [
            'allowedStages' => array_values(array_filter($applicationForm->status->allowedNextStages(),
                fn (CandidatePipelineStage $stage): bool => Gate::allows('managePipeline', $applicationForm)
                    && ($stage !== CandidatePipelineStage::Selected || Gate::allows('select', $applicationForm)))),
            'application' => $applicationForm,
            'canSendCandidateEmail' => $canSendEmail,
            'cancelEmailSubject' => $templateDefaults['interview_cancel']['subject'] ?? '',
            'cancelEmailBody' => $templateDefaults['interview_cancel']['body'] ?? '',
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
