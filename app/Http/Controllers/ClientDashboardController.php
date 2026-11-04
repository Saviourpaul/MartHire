<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Enums\JobAlertFrequency;
use App\Services\AccountGreetingService;
use App\Services\ApplicantProfileChecklistService;
use App\Services\ApplicantProfileService;
use App\Services\CandidateJobPreferenceService;
use App\Services\LocationService;
use App\Support\LiveTables;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function index(Request $request, AccountGreetingService $greetings, ApplicantProfileChecklistService $profileChecklist, ApplicantProfileService $profiles, CandidateJobPreferenceService $jobPreferences): View|JsonResponse
    {
        $user = $request->user();
        $greeting = $greetings->forUser($user);

        $applicationsQuery = $user->applications()
            ->getQuery()
            ->with(['job:id,title,company,location'])
            ->latest('submitted_at')
            ->latest('id')
            ->select(['id', 'reference', 'job_id', 'status', 'submitted_at']);
        $applications = LiveTables::paginate($applicationsQuery, 10, 'applications_page')->withQueryString();

        $suggestedJobs = Job::query()
            ->acceptingApplications()
            ->whereNotIn('id', $user->applications()->pluck('job_id'))
            ->latest('created_at')
            ->limit(6)
            ->get(['id', 'title', 'company', 'location', 'employment_type', 'category']);

        return LiveTables::view($request, view('client.dashboard', [
            'user' => $user,
            'applications' => $applications,
            'suggestedJobs' => $suggestedJobs,
            'profileChecklist' => $profileChecklist->forUser($user),
            'applicantProfile' => $profiles->forUser($user),
            'jobPreferences' => $jobPreferences->forUser($user),
            'countries' => app(LocationService::class)->countries(),
            'jobTypes' => Job::EMPLOYMENT_TYPES,
            'jobAlertFrequencies' => JobAlertFrequency::cases(),
            'isLiveTableRequest' => LiveTables::requested($request),
            'greeting' => $greeting['message'],
            'greetingEmoji' => $greeting['emoji'],
        ]), ['client-dashboard']);
    }
}
