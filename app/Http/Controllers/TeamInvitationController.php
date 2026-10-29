<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptTeamInvitationRequest;
use App\Models\User;
use App\Services\OrganizationTeamService;
use App\Support\RateLimiting\AbuseGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class TeamInvitationController extends Controller
{
    public function __construct(
        private readonly OrganizationTeamService $teams,
        private readonly AbuseGuard $abuseGuard,
    ) {}

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
            'canAcceptExisting' => $user && $user->isOrganization() && ! $user->isSuspended()
                && hash_equals(Str::lower($user->email), Str::lower($invitation->email)),
        ])->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function accept(AcceptTeamInvitationRequest $request, string $token): RedirectResponse
    {
        $this->abortIfTokenLocked($token);

        try {
            $member = $this->teams->accept($token, $request->user(), $request->validated());
        } catch (Throwable $exception) {
            $this->abuseGuard->recordTokenFailure($token);

            throw $exception;
        }

        $this->abuseGuard->clearTokenFailures($token);
        Auth::guard('web')->login($member->user);
        $request->session()->regenerate();
        $request->session()->put('active_organization_id', $member->organization_id);
        $request->session()->forget('url.intended');

        return to_route('dashboard')->with('success', 'You have joined '.$member->organization->name.'.');
    }

    public function decline(Request $request, string $token): RedirectResponse
    {
        $this->abortIfTokenLocked($token);

        try {
            $this->teams->decline($token, $request->user());
        } catch (Throwable $exception) {
            $this->abuseGuard->recordTokenFailure($token);

            throw $exception;
        }

        $this->abuseGuard->clearTokenFailures($token);

        return to_route($request->user() ? 'dashboard' : 'login')->with('status', 'Invitation declined.');
    }

    /**
     * Stop processing once a single invitation token has failed too many times,
     * so a guessed or replayed token cannot be hammered indefinitely.
     */
    private function abortIfTokenLocked(string $token): void
    {
        $maxFailures = (int) config('rate_limiting.limits.invitation.token_failures', 5);

        if ($this->abuseGuard->tokenLocked($token, $maxFailures)) {
            abort(Response::HTTP_TOO_MANY_REQUESTS, 'Too many attempts for this invitation. Please request a new invite.');
        }
    }
}
