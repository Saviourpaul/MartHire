<?php

namespace App\Http\Controllers;

use App\Enums\SecurityActionPurpose;
use App\Http\Requests\SensitiveAccountActionRequest;
use App\Models\User;
use App\Services\AccountLifecycleService;
use App\Services\SecurityChallengeService;
use Illuminate\Http\RedirectResponse;

class SecuritySessionController extends Controller
{
    public function destroy(SensitiveAccountActionRequest $request, SecurityChallengeService $challenges, AccountLifecycleService $accounts): RedirectResponse
    {
        $challenges->confirm($request, SecurityActionPurpose::SessionsRevokeOthers, fn (User $user) => $accounts->revokeOthers($request, $user));

        return back()->with('success', 'Other devices have been signed out.');
    }
}
