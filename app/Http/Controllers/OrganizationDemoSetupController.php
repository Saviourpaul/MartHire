<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteOrganizationDemoSetupRequest;
use App\Services\OrganizationDemoRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrganizationDemoSetupController extends Controller
{
    public function show(Request $request, string $token, OrganizationDemoRequestService $demoRequests): View|Response
    {
        $demoRequest = $demoRequests->setupInvitation($token);
        abort_unless($demoRequest->setup_signed_at === null || $request->hasValidSignature(), 403, 'This setup link is not valid.');

        return response()->view('organization-demo.setup', [
            'demoRequest' => $demoRequest,
            'token' => $token,
        ])->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function store(CompleteOrganizationDemoSetupRequest $request, string $token, OrganizationDemoRequestService $demoRequests): RedirectResponse
    {
        $demoRequest = $demoRequests->setupInvitation($token);
        abort_unless($demoRequest->setup_signed_at === null || $request->hasValidSignature(), 403, 'This setup link is not valid.');
        [$user, $organization] = $demoRequests->completeSetup($token, $request->validated());

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('auth.version', (int) $user->authentication_version);
        $request->session()->put('active_organization_id', $organization->id);
        $request->session()->forget('url.intended');

        return to_route('dashboard')->with('success', 'Your organization account is ready. Finish setting up your workspace.');
    }
}
