<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCandidateJobPreferencesRequest;
use App\Services\CandidateJobPreferenceService;
use Illuminate\Http\RedirectResponse;

class CandidateJobPreferenceController extends Controller
{
    public function __construct(private CandidateJobPreferenceService $preferences) {}

    public function update(UpdateCandidateJobPreferencesRequest $request): RedirectResponse
    {
        $this->preferences->update($request->user(), $request->validated());

        return to_route('settings.edit')->with('success', 'Job preferences saved.');
    }
}
