<?php

namespace App\Http\Controllers\Auth;

use App\Enums\SecurityActionPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountPasswordRequest;
use App\Models\User;
use App\Services\AccountLifecycleService;
use App\Services\SecurityChallengeService;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(UpdateAccountPasswordRequest $request, SecurityChallengeService $challenges, AccountLifecycleService $accounts): RedirectResponse
    {
        $challenges->confirm($request, SecurityActionPurpose::PasswordChange,
            fn (User $user) => $accounts->changePassword($user, $request->validated('password'), $request));

        return back()->with('status', 'password-updated');
    }
}
