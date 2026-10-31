<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Support\LiveTables;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

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
            'applications' => $applications,
            'suggestedJobs' => $suggestedJobs,
            'profileCompletion' => $user->applicantProfileCompletionPercentage(),
            'missingProfileFields' => $user->missingApplicantProfileFields(),
        ]), ['client-dashboard']);
    }
}
