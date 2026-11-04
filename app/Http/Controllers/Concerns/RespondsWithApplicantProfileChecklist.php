<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\JobAlertFrequency;
use App\Models\Job;
use App\Models\User;
use App\Services\ApplicantProfileChecklistService;
use App\Services\ApplicantProfileService;
use App\Services\CandidateJobPreferenceService;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;

trait RespondsWithApplicantProfileChecklist
{
    protected function applicantProfileChecklistResponse(User $user, string $message): JsonResponse
    {
        $freshUser = $user->fresh() ?? $user;
        $checklist = app(ApplicantProfileChecklistService::class)->forUser($freshUser);
        $editorData = [
            'user' => $freshUser,
            'applicantProfile' => app(ApplicantProfileService::class)->forUser($freshUser),
            'jobPreferences' => app(CandidateJobPreferenceService::class)->forUser($freshUser),
            'countries' => app(LocationService::class)->countries(),
            'jobTypes' => Job::EMPLOYMENT_TYPES,
            'jobAlertFrequencies' => JobAlertFrequency::cases(),
        ];

        return response()->json([
            'message' => $message,
            'progress' => [
                'completed' => $checklist['completed'],
                'total' => $checklist['total'],
                'percent' => $checklist['percent'],
            ],
            'checklist_html' => view('profile.partials.applicant-profile-checklist-fragment', compact('checklist'))->render(),
            'editor_html' => view('profile.partials.applicant-profile-checklist-editor', $editorData)->render(),
        ]);
    }
}
