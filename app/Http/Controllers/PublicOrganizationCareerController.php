<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\PublicJobService;
use Illuminate\View\View;

class PublicOrganizationCareerController extends Controller
{
    public function __invoke(Organization $organization, PublicJobService $jobs): View
    {
        abort_unless(filled($organization->slug), 404);
        $organization->load('settings');

        return view('organisations.careers.show', [
            'organization' => $organization,
            'settings' => $organization->settings,
            'jobs' => $jobs->forOrganization($organization),
        ]);
    }
}
