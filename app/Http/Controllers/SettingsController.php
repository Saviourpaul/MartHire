<?php

namespace App\Http\Controllers;

use App\Enums\JobAlertFrequency;
use App\Enums\SocialPlatform;
use App\Http\Requests\UpdateAppearanceRequest;
use App\Models\Job;
use App\Models\SettingsAuditEvent;
use App\Services\AccountLifecycleService;
use App\Services\CandidateJobPreferenceService;
use App\Services\NotificationPreferenceService;
use App\Services\OrganizationSettingsService;
use App\Services\PlatformSettingsService;
use App\Services\UserPreferencesService;
use App\Support\OrganizationContext;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private UserPreferencesService $preferences, private OrganizationContext $context) {}

    public function edit(Request $request, NotificationPreferenceService $notifications, AccountLifecycleService $security, CandidateJobPreferenceService $jobPreferences, PlatformSettingsService $platformSettings, OrganizationSettingsService $organizationSettings): View
    {
        $user = $request->user();
        $organization = $user->isOrganization() ? $this->context->organization() : null;
        $canUpdateOrganizationSettings = $organization !== null && $user->can('update', $organization);

        return view('settings.settings', [
            'userPreferences' => $this->preferences->forUser($user),
            'timezones' => DateTimeZone::listIdentifiers(),
            'organization' => $organization,
            'organizationSettings' => $canUpdateOrganizationSettings ? $organizationSettings->forOrganization($organization) : null,
            'securitySessions' => $security->sessions($request),
            'securityActivity' => SettingsAuditEvent::query()->where('user_id', $user->id)
                ->where('scope', 'personal')->whereIn('action', ['password_changed', 'password_reset', 'other_sessions_revoked', 'all_sessions_revoked'])
                ->latest('created_at')->limit(10)->get(['action', 'created_at']),
            'jobPreferences' => $user->isApplicant() ? $jobPreferences->forUser($user) : null,
            'jobTypes' => Job::EMPLOYMENT_TYPES,
            'jobAlertFrequencies' => JobAlertFrequency::cases(),
            'platformSettings' => $user->isAdmin() ? $platformSettings->current() : null,
            'socialPlatforms' => $user->isAdmin() ? SocialPlatform::cases() : [],
            ...$notifications->form($user),
        ]);
    }

    public function updateAppearance(UpdateAppearanceRequest $request): RedirectResponse
    {
        $this->preferences->update($request->user(), $request->validated());

        return to_route('settings.edit')->with('success', 'Display timezone saved.');
    }
}
