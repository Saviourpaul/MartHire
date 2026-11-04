<?php

namespace App\Services;

use App\Enums\InterviewTemplateType;
use App\Models\ApplicationForm;
use App\Models\EmailTemplate;
use App\Models\User;
use DateTimeZone;
use Illuminate\Database\Eloquent\Collection;

class InterviewWorkspaceData
{
    public function __construct(
        private OrganizationSettingsService $organizationSettings,
        private UserPreferencesService $userPreferences,
        private EmailTemplateService $emailTemplates,
    ) {}

    /**
     * @param  Collection<int, EmailTemplate>|null  $effectiveTemplates
     * @return array{timezones: list<string>, timezone: string, contact_name: string, contact_phone: ?string, email_templates: list<array{type: string, label: string, subject: string, body: string}>}
     */
    public function forApplication(ApplicationForm $application, User $actor, ?Collection $effectiveTemplates = null): array
    {
        $settings = $this->organizationSettings->forOrganization($application->organization_id);
        $effectiveTemplates ??= $this->emailTemplates->forSending($application->organization_id);
        $emailTemplates = $effectiveTemplates
            ->reject(fn ($template): bool => $template->type === InterviewTemplateType::InterviewCancel)
            ->map(fn ($template): array => [
                'type' => $template->type->value,
                'label' => $template->type->label(),
                'subject' => $template->subject,
                'body' => $template->body,
            ])->values()->all();

        return [
            'timezones' => DateTimeZone::listIdentifiers(),
            'timezone' => $settings->default_interview_timezone
                ?? $this->userPreferences->forUser($actor)->timezone
                ?? config('app.timezone', 'UTC'),
            'contact_name' => $settings->default_interview_contact_name ?? trim($actor->first_name.' '.$actor->last_name),
            'contact_phone' => $settings->default_interview_contact_phone ?? $actor->phone,
            'email_templates' => $emailTemplates,
        ];
    }
}
