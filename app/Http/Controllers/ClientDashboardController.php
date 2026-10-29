<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $applications = $user->applications()
            ->with(['job:id,title,company,location'])
            ->latest('submitted_at')
            ->limit(5)
            ->get(['id', 'reference', 'job_id', 'status', 'submitted_at']);

        $suggestedJobs = Job::query()
            ->acceptingApplications()
            ->whereNotIn('id', $user->applications()->pluck('job_id'))
            ->latest('created_at')
            ->limit(6)
            ->get(['id', 'title', 'company', 'location', 'employment_type', 'category']);

        return view('client.dashboard', [
            'applications' => $applications,
            'suggestedJobs' => $suggestedJobs,
            'profileCompletion' => $user->applicantProfileCompletionPercentage(),
            'missingProfileFields' => $user->missingApplicantProfileFields(),
        ]);
    }
}
