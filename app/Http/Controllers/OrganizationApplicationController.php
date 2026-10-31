<?php

namespace App\Http\Controllers;

use App\Enums\CandidatePipelineStage;
use App\Http\Requests\MoveCandidatePipelineRequest;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Services\ApplicationDocumentFileService;
use App\Services\ApplicationFormService;
use App\Services\CandidateReviewService;
use App\Services\EmailTemplateService;
use App\Services\InterviewWorkspaceData;
use App\Support\LiveTables;
use App\Support\OrganizationPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationApplicationController extends Controller
{
    public function applied(Request $request): View|JsonResponse
    {
        $this->validateTableFilters($request);
        $stage = $request->filled('stage') ? CandidatePipelineStage::tryFrom($request->string('stage')->toString()) : null;

        abort_if($request->filled('stage') && ! $stage, 404);

        return $this->candidateTable($request, $stage, 'Candidate Pipeline', 'organisations.candidates.index', 'organisations.candidates.index');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->validateTableFilters($request);
        $stage = $request->filled('stage') ? CandidatePipelineStage::tryFrom($request->string('stage')->toString()) : null;

        abort_if($request->filled('stage') && ! $stage, 404);

        $applications = $this->filteredCandidates($request, $stage)
            ->with(['job:id,title,company', 'applicant:id,first_name,last_name,email'])
            ->latest('submitted_at')
            ->get();

        $filename = 'nearbyworks-candidates-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($applications): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Candidate', 'Email', 'Job', 'Stage', 'Applied']);

            foreach ($applications as $application) {
                fputcsv($handle, [
                    trim(($application->applicant?->first_name ?? '').' '.($application->applicant?->last_name ?? '')),
                    $application->applicant?->email ?? '',
                    $application->job?->title ?? '',
                    $application->status->label(),
                    $application->submitted_at?->toDateString() ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function show(Request $request, ApplicationForm $applicationForm): Response|JsonResponse
    {
        $this->ensureOrganizationOwnsApplication($request, $applicationForm);
        $canSendEmail = Gate::allows('send_candidate_email', $applicationForm);
        $relations = ['applicant', 'job',
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

        $documents = Gate::allows('viewDocuments', $applicationForm)
            ? $applicationForm->documents->sortBy(fn ($document): int => $document->document_type?->value === 'resume' ? 0 : 1)
                ->map(function ($document): array {
                    return [
                        'id' => (string) $document->id,
                        'label' => $document->displayLabel(),
                        'size' => $document->size,
                        'preview_url' => $document->previewUrl(),
                        'download_url' => $document->downloadUrl(),
                        ...app(ApplicationDocumentFileService::class)->metadataFor($document),
                    ];
                })->values()->all()
            : [];

        $response = LiveTables::view($request, view('organisations.candidates.show', [
            'allowedStages' => array_values(array_filter($applicationForm->status->allowedNextStages(),
                fn (CandidatePipelineStage $stage): bool => Gate::allows('managePipeline', $applicationForm)
                    && ($stage !== CandidatePipelineStage::Selected || Gate::allows('select', $applicationForm)))),
            'application' => $applicationForm,
            'review' => app(CandidateReviewService::class)->build($applicationForm, $request->user()),
            'reviewDocuments' => $documents,
            'interviewWorkspace' => $canSendEmail ? app(InterviewWorkspaceData::class)->forApplication($applicationForm, $request->user()) : [],
            'canSendCandidateEmail' => $canSendEmail,
            'cancelEmailSubject' => $templateDefaults['interview_cancel']['subject'] ?? '',
            'cancelEmailBody' => $templateDefaults['interview_cancel']['body'] ?? '',
        ]), ['candidate-review']);

        return $response instanceof JsonResponse ? $response : response($response)->header('Cache-Control', 'private, no-store');
    }

    public function movePipeline(MoveCandidatePipelineRequest $request, ApplicationForm $applicationForm, ApplicationFormService $service): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        $application = $service->moveCandidate(
            $applicationForm,
            $request->user(),
            CandidatePipelineStage::from($data['stage']),
            $data['remarks'] ?? null,
            $request->boolean('selection_confirmed')
        );

        $message = "Candidate moved to {$application->status->label()}.";

        return LiveTables::mutation($request, $message, back()->with('success', $message), ['candidates', 'applications']);
    }

    /**
     * The same filtered query behind both the table view (paginated) and the
     * CSV export (streamed in full) — status/job/search filtering in one place.
     *
     * @return Builder<ApplicationForm>
     */
    private function validateTableFilters(Request $request): void
    {
        $request->validate(['stage' => ['sometimes', 'nullable', 'string', 'max:40'], 'search' => ['sometimes', 'nullable', 'string', 'max:200'],
            'job_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'min:1']]);
    }

    private function filteredCandidates(Request $request, ?CandidatePipelineStage $status): Builder
    {
        return ApplicationForm::query()
            ->forEmployer($request->user())
            ->when($status, fn ($query) => $query->status($status))
            ->when($request->filled('job_id'), fn ($query) => $query->where('job_id', $request->integer('job_id')))
            ->search($request->string('search')->toString());
    }

    private function candidateTable(Request $request, ?CandidatePipelineStage $status, string $title, string $routeName, string $viewName): View|JsonResponse
    {
        $applications = LiveTables::paginate($this->filteredCandidates($request, $status)
            ->with(['job:id,title,company', 'applicant:id,first_name,last_name,email'])
            ->withCount('documents')
            ->latest('submitted_at')->latest('id'), 10);

        $jobs = Job::query()
            ->tap(fn ($query) => app(OrganizationPermissions::class)->scopeJobs($query, $request->user()))
            ->whereHas('applications')
            ->orderBy('title')
            ->get(['id', 'title']);

        return LiveTables::view($request, view($viewName, [
            'applications' => $applications,
            'jobs' => $jobs,
            'statusFilter' => $status,
            'exportRoute' => 'organisations.candidates.export',
            'title' => $title,
            'routeName' => $routeName,
        ]), ['organization-candidates']);
    }

    private function ensureOrganizationOwnsApplication(Request $request, ApplicationForm $application): void
    {
        Gate::authorize('viewForEmployer', $application);
    }
}
