<?php

namespace App\Services;

use App\Models\ApplicationForm;
use App\Models\User;
use DateTimeZone;

class InterviewWorkspaceData
{
    public function __construct(
        private OrganizationSettingsService $organizationSettings,
        private UserPreferencesService $userPreferences,
    ) {}

    /** @return array{timezones: list<string>, timezone: string, contact_name: string, contact_phone: ?string} */
    public function forApplication(ApplicationForm $application, User $actor): array
    {
        $settings = $this->organizationSettings->forOrganization($application->organization_id);

        return [
            'timezones' => DateTimeZone::listIdentifiers(),
            'timezone' => $settings->default_interview_timezone
                ?? $this->userPreferences->forUser($actor)->timezone
                ?? config('app.timezone', 'UTC'),
            'contact_name' => $settings->default_interview_contact_name ?? trim($actor->first_name.' '.$actor->last_name),
            'contact_phone' => $settings->default_interview_contact_phone ?? $actor->phone,
        ];
    }
}
