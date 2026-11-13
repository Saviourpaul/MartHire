<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitOrganizationDemoInformationRequest;
use App\Services\OrganizationDemoRequestService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class OrganizationDemoFollowupController extends Controller
{
    public function show(string $token, OrganizationDemoRequestService $demoRequests): View|Response
    {
        $demoRequest = $demoRequests->requesterInformation($token);

        return response()->view('organization-demo.followup', compact('demoRequest', 'token'))
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function store(SubmitOrganizationDemoInformationRequest $request, string $token, OrganizationDemoRequestService $demoRequests): Response
    {
        $demoRequest = $demoRequests->submitRequesterInformation($token, $request->validated('response'));

        return response()->view('organization-demo.followup-complete', ['companyName' => $demoRequest->company_name])
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
