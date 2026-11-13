<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Services\PublicJobService;
use Illuminate\View\View;

class PublicJobController extends Controller
{
    public function home(PublicJobService $jobs): View
    {
        return view('Home', ['jobs' => $jobs->homepage()]);
    }

    public function index(PublicJobService $jobs): View
    {
        return view('Jobs', ['jobs' => $jobs->discover()]);
    }

    public function show(Job $job, PublicJobService $jobs): View
    {
        abort_unless($job->isAcceptingApplications(), 404);

        return view('JobDetail', [
            'job' => $jobs->native($job),
            'jobs' => $jobs->related($job),
            'id' => 'native:'.$job->id,
        ]);
    }
}
