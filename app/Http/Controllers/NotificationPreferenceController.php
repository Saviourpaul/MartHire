<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMembershipNotificationPreferencesRequest;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Models\Organization;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\RedirectResponse;

class NotificationPreferenceController extends Controller
{
    public function __construct(private NotificationPreferenceService $preferences) {}

    public function update(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        $this->preferences->update($request->user(), $request->validated('email_preferences'));

        return to_route('settings.edit')->with('success', 'Email preferences saved.');
    }

    public function updateMembership(UpdateMembershipNotificationPreferencesRequest $request, Organization $organization): RedirectResponse
    {
        $this->preferences->updateMembership($request->user(), $organization, $request->validated('membership_email_preferences'));

        return to_route('settings.edit')->with('success', 'Organization email preferences saved.');
    }
}
