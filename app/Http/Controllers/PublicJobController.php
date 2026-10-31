<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Services\PublicJobService;
use Illuminate\View\View;

class PublicJobController extends Controller
{
    public function home(PublicJobService $jobs): View
    {
        return view('Home', ['jobs' => $jobs->open()]);
    }

    public function index(PublicJobService $jobs): View
    {
        return view('Jobs', ['jobs' => $jobs->open()]);
    }

    public function show(Job $job, PublicJobService $jobs): View
    {
        abort_unless($job->isAcceptingApplications(), 404);

        return view('JobDetail', [
            'job' => $jobs->native($job),
            'jobs' => $jobs->open(),
            'id' => 'native:'.$job->id,
        ]);
    }
}
