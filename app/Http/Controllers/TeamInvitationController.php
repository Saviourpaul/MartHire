<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptTeamInvitationRequest;
use App\Models\User;
use App\Services\OrganizationTeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TeamInvitationController extends Controller
{
    public function __construct(private readonly OrganizationTeamService $teams) {}

    public function show(Request $request, string $token): Response
    {
        $invitation = $this->teams->availableInvitation($token);
        $existingAccount = User::withTrashed()->whereRaw('LOWER(email) = ?', [$invitation->email])->exists();
        $user = $request->user();

        if (! $user && $existingAccount) {
            $request->session()->put('url.intended', route('team-invitations.show', $token));
        }

        return response()->view('team-invitations.show', [
            'invitation' => $invitation,
            'organization' => $invitation->organization,
            'token' => $token,
            'existingAccount' => $existingAccount,
            'canAcceptExisting' => $user && $user->isEmployer() && ! $user->isSuspended()
                && hash_equals(Str::lower($user->email), Str::lower($invitation->email)),
        ])->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function accept(AcceptTeamInvitationRequest $request, string $token): RedirectResponse
    {
        $member = $this->teams->accept($token, $request->user(), $request->validated());
        Auth::guard('web')->login($member->user);
        $request->session()->regenerate();
        $request->session()->put('active_organization_id', $member->organization_id);
        $request->session()->forget('url.intended');

        return to_route('dashboard')->with('success', 'You have joined '.$member->organization->name.'.');
    }

    public function decline(Request $request, string $token): RedirectResponse
    {
        $this->teams->decline($token, $request->user());

        return to_route($request->user() ? 'dashboard' : 'login')->with('status', 'Invitation declined.');
    }
}
