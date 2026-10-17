<?php

namespace App\Http\Controllers;

use App\Enums\InterviewTemplateType;
use App\Http\Requests\CancelInterviewRequest;
use App\Http\Requests\SaveInterviewRequest;
use App\Models\ApplicationForm;
use App\Models\CandidateMessage;
use App\Models\Interview;
use App\Services\CandidateMessageService;
use App\Services\EmailTemplateService;
use App\Services\InterviewService;
use App\Services\InterviewTemplateRenderer;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EmployerInterviewController extends Controller
{
    public function __construct(
        private InterviewService $interviews,
        private EmailTemplateService $templates,
        private InterviewTemplateRenderer $renderer,
    ) {}

    public function create(ApplicationForm $applicationForm): View
    {
        Gate::authorize('create', [Interview::class, $applicationForm]);

        return $this->form($applicationForm);
    }

    public function store(SaveInterviewRequest $request, ApplicationForm $applicationForm): RedirectResponse
    {
        $this->interviews->schedule($applicationForm, $request->user(), $request->validated());

        return to_route('employer.applications.show', $applicationForm)
            ->with('success', 'Interview scheduled. Check the invitation delivery status below.');
    }

    public function edit(Interview $interview): View
    {
        Gate::authorize('update', $interview);

        return $this->form($interview->applicationForm, $interview);
    }

    public function update(SaveInterviewRequest $request, Interview $interview): RedirectResponse
    {
        $this->interviews->reschedule($interview, $request->user(), $request->validated());

        return to_route('employer.applications.show', $interview->application_form_id)
            ->with('success', 'Interview rescheduled. Check the update email delivery status below.');
    }

    public function cancel(CancelInterviewRequest $request, Interview $interview): RedirectResponse
    {
        $this->interviews->cancel($interview, $request->user(), $request->validated());

        return to_route('employer.applications.show', $interview->application_form_id)
            ->with('success', 'Interview cancelled. Check the cancellation email delivery status below.');
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

    public function retry(Request $request, CandidateMessage $candidateMessage, CandidateMessageService $messages): RedirectResponse
    {
        Gate::authorize('retry', $candidateMessage);
        $messages->retry($candidateMessage, $request->user());

        return to_route('employer.applications.show', $candidateMessage->application_form_id)
            ->with('success', 'Delivery retry requested. Check the email status below.');
    }

    private function form(ApplicationForm $application, ?Interview $interview = null): View
    {
        $selectedMode = old('mode', $interview?->mode?->value ?? 'online');
        $selectedTimezone = old('timezone', $interview?->timezone ?? config('app.timezone', 'UTC'));
        $localStart = $interview?->start_at?->setTimezone($interview->timezone);
        $localEnd = $interview?->end_at?->setTimezone($interview->timezone);
        $templateDefaults = $this->templates->forSending($application->organization_id)
            ->mapWithKeys(fn ($template): array => [$template->type->value => ['subject' => $template->subject, 'body' => $template->body]])->all();
        $templateType = $interview
            ? InterviewTemplateType::InterviewReschedule
            : ($selectedMode === 'onsite' ? InterviewTemplateType::InterviewOnsite : InterviewTemplateType::InterviewOnline);
        $emailDefaults = $templateDefaults[$templateType->value] ?? ['subject' => '', 'body' => ''];

        return view('employer.interviews.form', [
            'application' => $application->load('job'),
            'interview' => $interview,
            'timezones' => DateTimeZone::listIdentifiers(),
            'templateDefaults' => $templateDefaults,
            'selectedMode' => $selectedMode,
            'selectedTimezone' => $selectedTimezone,
            'interviewDate' => old('interview_date', $localStart?->format('Y-m-d')),
            'startTime' => old('start_time', $localStart?->format('H:i')),
            'endTime' => old('end_time', $localEnd?->format('H:i')),
            'emailSubject' => old('subject', $emailDefaults['subject']),
            'emailBody' => old('body', $emailDefaults['body']),
            'formAction' => $interview ? route('employer.interviews.update', $interview) : route('employer.interviews.store', $application),
            'formMethod' => $interview ? 'PATCH' : 'POST',
            'previewUrl' => route('employer.interviews.preview', $application),
        ]);
    }
}
