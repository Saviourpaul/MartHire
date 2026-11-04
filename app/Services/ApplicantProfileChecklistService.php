<?php

namespace App\Services;

use App\Models\User;
use App\Models\ApplicantProfile;
use App\Models\CandidateJobPreference;

class ApplicantProfileChecklistService
{
    public function __construct(private ApplicantProfileService $profiles, private CandidateJobPreferenceService $preferences) {}

    /** @return array{items: list<array<string, mixed>>, completed: int, total: int, percent: int} */
    public function forUser(User $user): array
    {
        $profile = $this->profiles->forUser($user);
        $preferences = $this->preferences->forUser($user);
        $savedIdentity = $profile->selfIdentification;
        $hasGenderIdentity = in_array($savedIdentity?->gender_identity, ['male', 'female'], true);
        $hasDisabilityStatus = in_array($savedIdentity?->disability_status, ['yes', 'no'], true);
        $hasDisabilityDetails = $savedIdentity?->disability_status !== 'yes' || filled($savedIdentity?->disability_details);
        $hasContactIdentification = $hasGenderIdentity && $hasDisabilityStatus && $hasDisabilityDetails;
        $contactMissing = $user->missingApplicantProfileFields();
        $hasContactDetails = $contactMissing === [];
        $hasAbout = filled($profile->professional_headline) && filled($profile->about);
        $hasSomeAbout = filled($profile->professional_headline) || filled($profile->about);
        $hasResume = $this->profiles->hasAvailableResume($profile);
        $hasEmployment = $profile->employments->isNotEmpty();
        $hasSkills = $profile->skills->isNotEmpty();
        $hasWorkPreferences = filled($preferences->preferred_roles)
            || filled($preferences->preferred_locations)
            || filled($preferences->preferred_job_type)
            || filled($preferences->preferred_workplace)
            || $preferences->salary_min !== null
            || $preferences->salary_max !== null;
        $hasLinks = $profile->links->isNotEmpty();

        $items = [
            $this->item('resume', __('Resume'), $hasResume, $profile->resume_original_name ?: __('Upload to autofill your profile'), route('profile.edit').'#resume', $hasResume ? __('Edit') : __('Add')),
            $this->item('contact', __('Contact information'), $hasContactDetails && $hasAbout && $hasContactIdentification, trim($user->first_name.' '.$user->last_name), route('profile.edit').'#contact-information', $hasContactDetails ? __('Edit') : __('Add'), partial: $hasContactDetails || $hasSomeAbout || $hasContactIdentification),
            $this->item('employment', __('Employment'), $hasEmployment && $hasSkills, $hasEmployment ? $profile->employments->first()->company.' · '.$profile->employments->first()->title : __('Companies and titles · Skills'), route('profile.edit').'#employment', $hasEmployment ? __('Edit') : __('Add'), partial: $hasEmployment || $hasSkills),
            $this->item('education', __('Education'), $profile->educations->isNotEmpty(), $profile->educations->first()?->institution ?? __('Schools and degrees'), route('profile.edit').'#education', $profile->educations->isNotEmpty() ? __('Edit') : __('Add')),
            $this->item('online_profiles', __('Online profiles'), $hasLinks, $hasLinks ? $profile->links->pluck('platform')->map(fn (string $platform): string => ucfirst($platform))->implode(', ') : __('LinkedIn, GitHub, website'), route('profile.edit').'#online-profiles', $hasLinks ? __('Edit') : __('Add')),
            $this->item('work_preferences', __('Work preferences'), (bool) $hasWorkPreferences, $hasWorkPreferences ? __('Preferences saved') : __('Worksites, employment type, location'), route('profile.edit').'#work-preferences', $hasWorkPreferences ? __('Edit') : __('Add')),
        ];

        $completed = collect($items)->where('complete', true)->count();
        $total = count($items);

        return [
            'items' => $items,
            'completed' => $completed,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 100,
        ];
    }

    /**
     * Return only profile fields the application form still needs to collect.
     * Core contact details and documents remain on their existing wizard steps.
     *
     * @return array<string, mixed>
     */
    public function applicationRequirements(
        User $user,
        ApplicantProfile $profile,
        CandidateJobPreference $preferences
    ): array {
        $identification = $profile->selfIdentification;
        $contact = [
            'professional_headline' => blank($profile->professional_headline),
            'about' => blank($profile->about),
            'disability_status' => ! in_array($identification?->disability_status, ['yes', 'no'], true),
            'disability_details' => $identification?->disability_status === 'yes' && blank($identification?->disability_details),
        ];
        $needsContactFields = in_array(true, $contact, true);
        $needsEmployment = $profile->employments->isEmpty();
        $needsSkills = $profile->skills->isEmpty();
        $needsEducation = $profile->educations->isEmpty();
        $needsOnlineProfiles = $profile->links->isEmpty();
        $needsWorkPreferences = ! (
            filled($preferences->preferred_roles)
            || filled($preferences->preferred_locations)
            || filled($preferences->preferred_job_type)
            || filled($preferences->preferred_workplace)
            || $preferences->salary_min !== null
            || $preferences->salary_max !== null
        );

        return [
            'resume' => ! $this->profiles->hasAvailableResume($profile),
            'identification' => $this->profiles->availableIdentificationDocument($user) === null,
            'contact' => $contact,
            'employment' => $needsEmployment,
            'skills' => $needsSkills,
            'education' => $needsEducation,
            'online_profiles' => $needsOnlineProfiles,
            'work_preferences' => $needsWorkPreferences,
            'has_additional_fields' => $needsContactFields || $needsEmployment || $needsSkills || $needsEducation || $needsOnlineProfiles || $needsWorkPreferences,
        ];
    }

    /** @return array<string, mixed> */
    private function item(string $key, string $label, bool $complete, string $description, string $url, string $action, bool $partial = false, bool $optional = false): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'url' => $url,
            'action' => $action,
            'complete' => $complete,
            'partial' => ! $complete && $partial,
            'optional' => $optional,
        ];
    }
}
