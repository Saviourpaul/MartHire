<?php

namespace App\Http\Controllers;

use App\Enums\JobAlertFrequency;
use App\Enums\SocialPlatform;
use App\Http\Requests\UpdateAppearanceRequest;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Http\Requests\UpdateNotificationSoundPreferenceRequest;
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
use Illuminate\Http\JsonResponse;
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

    public function updateNotificationPreferences(UpdateNotificationPreferencesRequest $request, NotificationPreferenceService $notifications): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (array_key_exists('email_preferences', $data)) {
            $notifications->update($user, $data['email_preferences']);
        }

        if (array_key_exists('membership_email_preferences', $data)) {
            $organization = $this->context->organization();
            abort_unless($organization !== null, 403);
            $notifications->updateMembership($user, $organization, $data['membership_email_preferences']);
        }

        return to_route('settings.edit')->with('success', 'Notification preferences saved.');
    }

    public function updateNotificationSound(UpdateNotificationSoundPreferenceRequest $request): JsonResponse
    {
        $preference = $this->preferences->updateNotificationSound(
            $request->user(),
            (bool) $request->validated('enabled'),
        );

        return response()->json([
            'enabled' => (bool) $preference->notification_sound_enabled,
            'message' => __('Notification sound preference saved.'),
        ]);
    }
}
