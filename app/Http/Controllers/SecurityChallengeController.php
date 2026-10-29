<?php

namespace App\Http\Controllers;

use App\Enums\SecurityActionPurpose;
use App\Http\Requests\StoreSecurityChallengeRequest;
use App\Services\SecurityChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SecurityChallengeController extends Controller
{
    public function store(StoreSecurityChallengeRequest $request, SecurityChallengeService $challenges): JsonResponse|RedirectResponse
    {
        $challenge = $challenges->issue($request, SecurityActionPurpose::from($request->validated('purpose')));

        if ($request->expectsJson()) {
            return response()->json(['challenge_id' => $challenge->id, 'purpose' => $challenge->purpose->value, 'expires_at' => $challenge->expires_at->toIso8601String()], 202);
        }

        return back()->with('security_challenge_id', $challenge->id)
            ->with('security_challenge_purpose', $challenge->purpose->value)
            ->with('success', 'A confirmation code has been requested. Check your email before continuing.');
    }
}
