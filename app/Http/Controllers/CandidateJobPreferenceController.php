<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApplicantProfileChecklist;
use App\Http\Requests\UpdateCandidateJobPreferencesRequest;
use App\Services\CandidateJobPreferenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class CandidateJobPreferenceController extends Controller
{
    use RespondsWithApplicantProfileChecklist;

    public function __construct(private CandidateJobPreferenceService $preferences) {}

    public function update(UpdateCandidateJobPreferencesRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $this->preferences->update($request->user(), $data);

        if ($request->expectsJson()) {
            return $this->applicantProfileChecklistResponse($request->user(), 'Job preferences saved.');
        }

        $destination = ($data['return_to'] ?? 'settings') === 'profile'
            ? route('profile.edit').'#work-preferences'
            : route('settings.edit');

        return redirect()->to($destination)->with('success', 'Job preferences saved.');
    }
}
