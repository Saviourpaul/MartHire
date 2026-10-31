<?php

namespace App\Http\Controllers;

use App\Enums\InterviewTemplateType;
use App\Http\Requests\CancelInterviewRequest;
use App\Http\Requests\CompleteInterviewRequest;
use App\Http\Requests\SaveInterviewRequest;
use App\Models\ApplicationForm;
use App\Models\CandidateMessage;
use App\Models\Interview;
use App\Services\CandidateMessageService;
use App\Services\InterviewService;
use App\Services\InterviewTemplateRenderer;
use App\Services\InterviewWorkspaceData;
use App\Support\LiveTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationInterviewController extends Controller
{
    public function __construct(
        private InterviewService $interviews,
        private InterviewTemplateRenderer $renderer,
        private InterviewWorkspaceData $workspace,
    ) {}

    public function create(ApplicationForm $applicationForm): View
    {
        Gate::authorize('create', [Interview::class, $applicationForm]);

        return $this->form($applicationForm);
    }

    public function store(SaveInterviewRequest $request, ApplicationForm $applicationForm): RedirectResponse|JsonResponse
    {
        $this->interviews->schedule($applicationForm, $request->user(), $request->validated());

        $message = 'Interview scheduled. Check the invitation delivery status below.';

        return LiveTables::mutation($request, $message, $this->backToWorkspace($applicationForm->id)->with('success', $message), ['interviews', 'candidates']);
    }

    public function edit(Interview $interview): View
    {
        Gate::authorize('update', $interview);

        return $this->form($interview->applicationForm, $interview);
    }

    public function update(SaveInterviewRequest $request, Interview $interview): RedirectResponse|JsonResponse
    {
        $this->interviews->reschedule($interview, $request->user(), $request->validated());

        $message = 'Interview rescheduled. Check the update email delivery status below.';

        return LiveTables::mutation($request, $message, $this->backToWorkspace($interview->application_form_id)->with('success', $message), ['interviews', 'candidates']);
    }

    public function cancel(CancelInterviewRequest $request, Interview $interview): RedirectResponse|JsonResponse
    {
        $this->interviews->cancel($interview, $request->user(), $request->validated());

        $message = 'Interview cancelled. Check the cancellation email delivery status below.';

        return LiveTables::mutation($request, $message, $this->backToWorkspace($interview->application_form_id)->with('success', $message), ['interviews', 'candidates']);
    }

    public function preview(SaveInterviewRequest $request, ApplicationForm $applicationForm): JsonResponse
    {
        $data = $request->validated();
        $type = $data['mode'] === 'online' ? InterviewTemplateType::InterviewOnline : InterviewTemplateType::InterviewOnsite;

        if ($request->filled('interview_id')) {
            $interview = Interview::whereKey($data['interview_id'])
                ->where('application_form_id', $applicationForm->id)->firstOrFail();
            Gate::authorize('update', $interview);
            $type = InterviewTemplateType::InterviewReschedule;
        }

        return response()->json($this->renderer->render(
            $this->interviews->draft($applicationForm->load('job'), $data),
            $type,
            $data['subject'] ?? null,
            $data['body'] ?? null,
        ))->header('Cache-Control', 'no-store, private');
    }

    public function complete(CompleteInterviewRequest $request, Interview $interview): RedirectResponse|JsonResponse
    {
        $this->interviews->complete($interview, $request->user(), $request->validated());

        $message = 'Interview marked completed. An authorized team member can now explicitly confirm the candidate as Selected.';

        return LiveTables::mutation($request, $message, $this->backToWorkspace($interview->application_form_id)->with('success', $message), ['interviews', 'candidates']);
    }

    public function retry(Request $request, CandidateMessage $candidateMessage, CandidateMessageService $messages): RedirectResponse|JsonResponse
    {
        Gate::authorize('retry', $candidateMessage);
        $messages->retry($candidateMessage, $request->user());

        $message = 'Delivery retry requested. Check the email status below.';

        return LiveTables::mutation($request, $message, $this->backToWorkspace($candidateMessage->application_form_id)->with('success', $message), ['interviews', 'candidates']);
    }

    private function backToWorkspace(int $applicationId): RedirectResponse
    {
        return redirect()->to(route('organisations.candidates.show', ['applicationForm' => $applicationId, 'tab' => 'interviews']).'#candidate-interviews');
    }

    private function form(ApplicationForm $application, ?Interview $interview = null): View
    {
        return view('organisations.interviews.form', [
            'application' => $application->load('job'),
            'interview' => $interview,
            'interviewWorkspace' => $this->workspace->forApplication($application, auth()->user()),
        ]);
    }
}
