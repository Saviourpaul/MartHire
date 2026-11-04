<?php

namespace App\Http\Requests;

use App\Enums\ApplicationDocumentType;
use App\Http\Requests\Concerns\NormalizesInternationalPhone;
use App\Http\Requests\Concerns\ValidatesLocationSelection;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Rules\InternationalPhoneNumber;
use App\Services\ApplicantProfileChecklistService;
use App\Services\ApplicantProfileService;
use App\Services\CandidateJobPreferenceService;
use App\Services\LocationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreApplicationFormRequest extends FormRequest
{
    use NormalizesInternationalPhone, ValidatesLocationSelection;

    public const PROFILE_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'webp'];

    public const PROFILE_IMAGE_MAX_KB = 5120;

    public const PROFILE_IMAGE_MIN_WIDTH = 200;

    public const PROFILE_IMAGE_MIN_HEIGHT = 200;

    public const PROFILE_IMAGE_MAX_WIDTH = 2000;

    public const PROFILE_IMAGE_MAX_HEIGHT = 2000;

    public const DOCUMENT_TYPES = ['pdf', 'jpg', 'jpeg', 'png'];

    public const RESUME_TYPES = ['pdf', 'doc', 'docx'];

    public const DOCUMENT_MAX_KB = 5120;

    private const PERSON_NAME_PATTERN = '/^[\pL\s\'-]+$/u';

    public function authorize(): bool
    {
        $job = $this->route('job');
        $user = $this->user();

        return $job instanceof Job
            && $user?->isApplicant()
            && $job->isAcceptingApplications();
    }

    protected function failedAuthorization(): void
    {
        $job = $this->route('job');

        if ($job instanceof Job && ! $job->isAcceptingApplications()) {
            abort(404);
        }

        parent::failedAuthorization();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $requirements = $this->applicationProfileRequirements();
        $savedIdentity = $user ? app(ApplicantProfileService::class)->availableIdentificationDocument($user) : null;
        $profile = $user ? app(ApplicantProfileService::class)->forUser($user) : null;
        $selfIdentification = $profile?->selfIdentification;
        $identityTypes = array_map(
            fn (ApplicationDocumentType $type): string => $type->value,
            ApplicationDocumentType::identityTypes()
        );
        if ($savedIdentity && ! in_array($savedIdentity->document_type->value, $identityTypes, true)) {
            $identityTypes[] = $savedIdentity->document_type->value;
        }

        $profileContactRules = [
            'profile_contact' => ['sometimes', 'array:professional_headline,about,gender_identity,disability_status,disability_details'],
            'profile_contact.professional_headline' => $requirements['contact']['professional_headline']
                ? ['required', 'string', 'min:2', 'max:160'] : ['prohibited'],
            'profile_contact.about' => $requirements['contact']['about']
                ? ['required', 'string', 'min:10', 'max:3000'] : ['prohibited'],
            'profile_contact.gender_identity' => ['prohibited'],
            'profile_contact.disability_status' => $requirements['contact']['disability_status']
                ? ['required', Rule::in(['yes', 'no'])] : ['prohibited'],
            'profile_contact.disability_details' => ($requirements['contact']['disability_details']
                || ($requirements['contact']['disability_status'] && $this->input('profile_contact.disability_status') === 'yes'))
                ? [Rule::requiredIf(true), 'string', 'min:2', 'max:500']
                : ($selfIdentification?->disability_status === 'yes' ? ['prohibited'] : ['nullable', 'prohibited']),
        ];

        $employmentRules = [
            'profile_employment' => ['sometimes', 'array:company,title,location,employment_type,start_date,end_date,is_current,description'],
            'profile_employment.company' => $requirements['employment'] ? ['required', 'string', 'min:2', 'max:255'] : ['prohibited'],
            'profile_employment.title' => $requirements['employment'] ? ['required', 'string', 'min:2', 'max:255'] : ['prohibited'],
            'profile_employment.location' => $requirements['employment'] ? ['nullable', 'string', 'max:255'] : ['prohibited'],
            'profile_employment.employment_type' => $requirements['employment']
                ? ['nullable', Rule::in(array_keys(Job::EMPLOYMENT_TYPES))] : ['prohibited'],
            'profile_employment.start_date' => $requirements['employment'] ? ['required', 'date', 'before_or_equal:today'] : ['prohibited'],
            'profile_employment.end_date' => $requirements['employment'] ? ['nullable', 'date', 'after_or_equal:profile_employment.start_date', 'before_or_equal:today'] : ['prohibited'],
            'profile_employment.is_current' => $requirements['employment'] ? ['nullable', 'boolean'] : ['prohibited'],
            'profile_employment.description' => $requirements['employment'] ? ['nullable', 'string', 'max:5000'] : ['prohibited'],
        ];

        $educationProfileRules = [
            'profile_education' => ['sometimes', 'array:institution,qualification,field_of_study,start_date,end_date,description'],
            'profile_education.institution' => $requirements['education'] ? ['required', 'string', 'min:2', 'max:255'] : ['prohibited'],
            'profile_education.qualification' => $requirements['education'] ? ['required', 'string', 'min:2', 'max:255'] : ['prohibited'],
            'profile_education.field_of_study' => $requirements['education'] ? ['nullable', 'string', 'max:255'] : ['prohibited'],
            'profile_education.start_date' => $requirements['education'] ? ['nullable', 'date', 'before_or_equal:today'] : ['prohibited'],
            'profile_education.end_date' => $requirements['education'] ? ['nullable', 'date', 'after_or_equal:profile_education.start_date', 'before_or_equal:today'] : ['prohibited'],
            'profile_education.description' => $requirements['education'] ? ['nullable', 'string', 'max:5000'] : ['prohibited'],
        ];

        $linkRules = [
            'profile_links' => ['sometimes', 'array:linkedin,github,website'],
            'profile_links.linkedin' => $requirements['online_profiles'] ? ['nullable', 'url', 'max:2048'] : ['prohibited'],
            'profile_links.github' => $requirements['online_profiles'] ? ['nullable', 'url', 'max:2048'] : ['prohibited'],
            'profile_links.website' => $requirements['online_profiles'] ? ['nullable', 'url', 'max:2048'] : ['prohibited'],
        ];

        $preferenceRules = [
            'profile_preferences' => ['sometimes', 'array:preferred_roles,preferred_locations,preferred_job_type,preferred_workplace,salary_min,salary_max'],
            'profile_preferences.preferred_roles' => $requirements['work_preferences'] ? ['nullable', 'array', 'max:20'] : ['prohibited'],
            'profile_preferences.preferred_roles.*' => ['required', 'string', 'min:2', 'max:100', 'distinct:ignore_case'],
            'profile_preferences.preferred_locations' => $requirements['work_preferences'] ? ['nullable', 'array', 'max:20'] : ['prohibited'],
            'profile_preferences.preferred_locations.*' => ['required', 'string', 'min:2', 'max:100', 'distinct:ignore_case'],
            'profile_preferences.preferred_job_type' => $requirements['work_preferences']
                ? ['nullable', Rule::in(array_keys(Job::EMPLOYMENT_TYPES))] : ['prohibited'],
            'profile_preferences.preferred_workplace' => $requirements['work_preferences']
                ? ['nullable', Rule::in(['on_site', 'hybrid', 'remote'])] : ['prohibited'],
            'profile_preferences.salary_min' => $requirements['work_preferences'] ? ['nullable', 'integer', 'min:0', 'max:100000000'] : ['prohibited'],
            'profile_preferences.salary_max' => $requirements['work_preferences'] ? ['nullable', 'integer', 'min:0', 'max:100000000', 'gte:profile_preferences.salary_min'] : ['prohibited'],
            'profile_skills' => $requirements['skills'] ? ['required', 'array', 'min:1', 'max:30'] : ['prohibited'],
            'profile_skills.*' => ['required', 'string', 'min:1', 'max:80', 'distinct:ignore_case'],
        ];

        return [
            'profile_image' => [
                'bail',
                Rule::requiredIf(fn (): bool => blank($user?->profile_image_path)),
                File::image()
                    ->types(self::PROFILE_IMAGE_TYPES)
                    ->max(self::PROFILE_IMAGE_MAX_KB),
                sprintf(
                    'dimensions:min_width=%d,min_height=%d,max_width=%d,max_height=%d',
                    self::PROFILE_IMAGE_MIN_WIDTH,
                    self::PROFILE_IMAGE_MIN_HEIGHT,
                    self::PROFILE_IMAGE_MAX_WIDTH,
                    self::PROFILE_IMAGE_MAX_HEIGHT
                ),
            ],
            'first_name' => ['bail', 'required', 'string', 'min:2', 'max:100', 'regex:'.self::PERSON_NAME_PATTERN],
            'middle_name' => ['bail', 'nullable', 'string', 'max:100', 'regex:'.self::PERSON_NAME_PATTERN],
            'last_name' => ['bail', 'required', 'string', 'min:2', 'max:100', 'regex:'.self::PERSON_NAME_PATTERN],
            'email' => ['bail', 'required', 'string', 'lowercase', 'email:rfc', 'max:255'],
            'phone' => ['bail', 'required', 'string', 'max:80', new InternationalPhoneNumber($this->input('phone_country'))],
            ...$this->phonePartRules(),
            'date_of_birth' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before:today'],
            'gender' => ['bail', 'required', Rule::in(['male', 'female'])],
            'marital_status' => ['bail', 'required', Rule::in(['single', 'married', 'Other'])],
            ...$this->exclusiveLocationSelectionRules(['bail', 'required']),
            'address' => ['bail', 'required', 'string', 'min:5', 'max:255'],
            'identification_type' => [
                'bail',
                Rule::requiredIf($savedIdentity === null),
                'nullable',
                Rule::in($identityTypes),
            ],
            'identification_document' => [
                'bail',
                Rule::requiredIf($savedIdentity === null),
                File::types(self::DOCUMENT_TYPES)->max(self::DOCUMENT_MAX_KB),
            ],
            'resume' => [
                'bail',
                Rule::requiredIf($requirements['resume']),
                File::types(self::RESUME_TYPES)->max(self::DOCUMENT_MAX_KB),
            ],
            'education_documents' => ['bail', 'required', 'array', 'min:1', 'max:10'],
            'education_documents.*.name' => ['bail', 'required', 'string', 'min:2', 'max:100'],
            'education_documents.*.file' => [
                'bail',
                'required',
                File::types(self::DOCUMENT_TYPES)->max(self::DOCUMENT_MAX_KB),
            ],
            ...$profileContactRules,
            ...$employmentRules,
            ...$educationProfileRules,
            ...$linkRules,
            ...$preferenceRules,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens, and apostrophes.',
            'middle_name.regex' => 'The middle name may only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens, and apostrophes.',
            'date_of_birth.date_format' => 'Enter the date of birth in YYYY-MM-DD format.',
            'date_of_birth.after_or_equal' => 'Enter a realistic date of birth.',
            'date_of_birth.before' => 'The date of birth must be before today.',
            'identification_type.in' => 'Select a supported identification method.',
            'profile_image.dimensions' => sprintf(
                'The profile photo must be between %dx%d and %dx%d pixels.',
                self::PROFILE_IMAGE_MIN_WIDTH,
                self::PROFILE_IMAGE_MIN_HEIGHT,
                self::PROFILE_IMAGE_MAX_WIDTH,
                self::PROFILE_IMAGE_MAX_HEIGHT
            ),
            'resume.required' => 'Upload your résumé / CV.',
            'profile_employment.start_date.before_or_equal' => 'The employment start date cannot be in the future.',
            'education_documents.max' => 'You can upload a maximum of 10 documents.',
            'education_documents.*.name.required' => 'Give each document a title.',
            'education_documents.*.file.required' => 'Upload a file for each document.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'profile_image' => 'profile photo',
            'first_name' => 'first name',
            'middle_name' => 'middle name',
            'last_name' => 'last name',
            'date_of_birth' => 'date of birth',
            'gender' => 'gender identity',
            'country_code' => 'country',
            'state' => 'state',
            'city' => 'city',
            'local_government_area' => 'local government area',
            'identification_type' => 'identification method',
            'identification_document' => 'identification document',
            'resume' => 'résumé / CV',
            'education_documents' => 'documents',
            'education_documents.*.name' => 'document title',
            'education_documents.*.file' => 'document file',
            'profile_contact.professional_headline' => 'professional headline',
            'profile_contact.about' => 'about you',
            'profile_contact.gender_identity' => 'gender identity',
            'profile_contact.disability_status' => 'disability status',
            'profile_contact.disability_details' => 'disability details',
            'profile_employment.company' => 'company',
            'profile_employment.title' => 'job title',
            'profile_employment.start_date' => 'employment start date',
            'profile_skills' => 'skills',
            'profile_education.institution' => 'school or institution',
            'profile_education.qualification' => 'degree or qualification',
            'profile_links.linkedin' => 'LinkedIn profile',
            'profile_links.github' => 'GitHub profile',
            'profile_links.website' => 'website',
            'profile_preferences.preferred_roles' => 'preferred roles',
            'profile_preferences.preferred_locations' => 'preferred locations',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $job = $this->route('job');
            $user = $this->user();

            if (! $job instanceof Job || ! $user) {
                return;
            }

            $alreadyApplied = ApplicationForm::query()
                ->where('job_id', $job->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyApplied) {
                $validator->errors()->add('job', 'You have already applied for this job.');
            }

            $requirements = $this->applicationProfileRequirements();
            if ($requirements['online_profiles'] && collect($this->input('profile_links', []))->filter(fn ($url) => filled($url))->isEmpty()) {
                $validator->errors()->add('profile_links.linkedin', 'Add at least one online profile link.');
            }

            if ($requirements['work_preferences']) {
                $preferences = $this->input('profile_preferences', []);
                $preferences = is_array($preferences) ? $preferences : [];
                $hasPreference = filled($preferences['preferred_roles'] ?? null)
                    || filled($preferences['preferred_locations'] ?? null)
                    || filled($preferences['preferred_job_type'] ?? null)
                    || filled($preferences['preferred_workplace'] ?? null)
                    || filled($preferences['salary_min'] ?? null)
                    || filled($preferences['salary_max'] ?? null);

                if (! $hasPreference) {
                    $validator->errors()->add('profile_preferences.preferred_roles', 'Add at least one work preference.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $countryCode = $this->input('country_code');
        $countryCode = is_string($countryCode) ? mb_strtoupper(trim($countryCode)) : null;
        $country = collect(app(LocationService::class)->countries())->firstWhere('value', $countryCode);
        $this->merge(['country_code' => $countryCode, 'country' => $country['label'] ?? null]);
        $this->normalizeLocationForCountry();
        $this->normalizePhoneInput();

        if (! $this->hasFile('identification_document') && $this->user()) {
            $savedIdentity = app(ApplicantProfileService::class)->availableIdentificationDocument($this->user());
            if ($savedIdentity) {
                $this->merge(['identification_type' => $savedIdentity->document_type->value]);
            }
        }

        $skills = $this->input('profile_skills');
        if (is_string($skills)) {
            $skills = explode(',', $skills);
        }
        if (is_array($skills)) {
            $this->merge([
                'profile_skills' => collect($skills)
                    ->filter(fn ($skill) => is_string($skill))
                    ->map(fn (string $skill) => trim($skill))
                    ->filter()
                    ->unique(fn (string $skill) => mb_strtolower($skill))
                    ->values()
                    ->all(),
            ]);
        }

        $preferences = $this->input('profile_preferences');
        if (is_array($preferences)) {
            foreach (['preferred_roles', 'preferred_locations'] as $field) {
                if (is_string($preferences[$field] ?? null)) {
                    $preferences[$field] = explode(',', $preferences[$field]);
                }
                if (is_array($preferences[$field] ?? null)) {
                    $preferences[$field] = collect($preferences[$field])
                        ->filter(fn ($item) => is_string($item))
                        ->map(fn (string $item) => trim($item))
                        ->filter()
                        ->unique(fn (string $item) => mb_strtolower($item))
                        ->values()
                        ->all();
                }
            }
            $this->merge(['profile_preferences' => $preferences]);
        }

        $employment = $this->input('profile_employment');
        if (is_array($employment) && filter_var($employment['is_current'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $employment['end_date'] = null;
            $this->merge(['profile_employment' => $employment]);
        }
    }

    /** @return array<string, mixed> */
    private function applicationProfileRequirements(): array
    {
        $user = $this->user();

        if (! $user) {
            return [
                'resume' => true,
                'identification' => true,
                'contact' => array_fill_keys(['professional_headline', 'about', 'disability_status', 'disability_details'], true),
                'employment' => true,
                'skills' => true,
                'education' => true,
                'online_profiles' => true,
                'work_preferences' => true,
                'has_additional_fields' => true,
            ];
        }

        $profiles = app(ApplicantProfileService::class);
        $profile = $profiles->forUser($user);

        return app(ApplicantProfileChecklistService::class)->applicationRequirements(
            $user,
            $profile,
            app(CandidateJobPreferenceService::class)->forUser($user)
        );
    }
}
