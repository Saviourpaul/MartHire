<?php

namespace App\Http\Controllers;

use App\Enums\InterviewCandidateResponse;
use App\Enums\InterviewStatus;
use App\Models\Interview;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Services\InterviewService;
use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InterviewResponseController extends Controller
{
    public function __construct(private OrganizationContext $context, private InterviewService $interviews) {}

    public function show(Request $request, int $interview, InterviewCandidateResponse $response): Response
    {
        return $this->withInterview($request, $interview, function (Interview $record) use ($request, $response): Response {
            $this->ensureAvailable($request, $record);

            return response()->view('interviews.response', [
                'interview' => $record,
                'application' => $record->applicationForm,
                'response' => $response,
                'actionUrl' => $request->fullUrl(),
            ], 200, $this->headers());
        });
    }

    public function store(Request $request, int $interview, InterviewCandidateResponse $response): Response
    {
        return $this->withInterview($request, $interview, function (Interview $record) use ($request, $response): Response {
            $this->ensureAvailable($request, $record);
            $record = $this->interviews->respond($record, $response, $request->integer('version'));

            return response()->view('interviews.responded', [
                'interview' => $record,
                'application' => $record->applicationForm,
                'response' => $record->candidate_response,
            ], 200, $this->headers());
        });
    }

    private function withInterview(Request $request, int $id, \Closure $callback): Response
    {
        $interview = Interview::withoutGlobalScope(OrganizationScope::class)->findOrFail($id);
        $organization = Organization::findOrFail($interview->organization_id);

        return $this->context->runInOrganization($organization, function () use ($request, $interview, $callback): Response {
            $interview->load('applicationForm.job');

            if ($request->user()) {
                abort_unless($request->user()->isApplicant()
                    && $request->user()->id === $interview->applicationForm->user_id, 403);
            }

            return $callback($interview);
        });
    }

    private function ensureAvailable(Request $request, Interview $interview): void
    {
        abort_unless($request->integer('version') === $interview->version
            && in_array($interview->status, [InterviewStatus::Scheduled, InterviewStatus::Rescheduled], true)
            && $interview->end_at->isFuture(), 410);
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return ['Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer'];
    }
}
