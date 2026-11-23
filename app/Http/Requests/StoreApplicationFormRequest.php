<?php

namespace App\Http\Requests;

use App\Enums\ApplicationDocumentType;
use App\Enums\JobScreeningQuestionType;
use App\Http\Requests\Concerns\NormalizesInternationalPhone;
use App\Http\Requests\Concerns\ValidatesLocationSelection;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\JobScreeningQuestion;
use App\Rules\InternationalPhoneNumber;
use App\Services\ApplicantProfileChecklistService;
use App\Services\ApplicantProfileService;
use App\Services\LocationService;
use App\Support\ApplicationUploadLimits;
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

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();
        $job = $this->route('job');
        $profileRequirements = $this->applicationProfileRequirements();
        $savedIdentity = $user ? app(ApplicantProfileService::class)->availableIdentificationDocument($user) : null;
        $identityTypes = array_map(
            static fn (ApplicationDocumentType $type): string => $type->value,
            ApplicationDocumentType::identityTypes(),
        );
        if ($savedIdentity && ! in_array($savedIdentity->document_type->value, $identityTypes, true)) {
            $identityTypes[] = $savedIdentity->document_type->value;
        }

        $countryCodes = array_column(app(LocationService::class)->countries(), 'value');
        $mustUploadIdentity = $savedIdentity === null;

        return [
            'profile_image' => [
                Rule::requiredIf($profileRequirements['profile_image']),
                'nullable',
                File::image()->types(self::PROFILE_IMAGE_TYPES)->max(self::PROFILE_IMAGE_MAX_KB),
                sprintf(
                    'dimensions:min_width=%d,min_height=%d,max_width=%d,max_height=%d',
                    self::PROFILE_IMAGE_MIN_WIDTH,
                    self::PROFILE_IMAGE_MIN_HEIGHT,
                    self::PROFILE_IMAGE_MAX_WIDTH,
                    self::PROFILE_IMAGE_MAX_HEIGHT,
                ),
            ],
            'first_name' => ['bail', 'required', 'string', 'min:2', 'max:100', 'regex:'.self::PERSON_NAME_PATTERN],
            'middle_name' => ['bail', 'nullable', 'string', 'max:100', 'regex:'.self::PERSON_NAME_PATTERN],
            'last_name' => ['bail', 'required', 'string', 'min:2', 'max:100', 'regex:'.self::PERSON_NAME_PATTERN],
            'email' => ['bail', 'required', 'string', 'lowercase', 'email:rfc', 'max:255'],
            'phone' => ['bail', 'required', 'string', 'max:80', new InternationalPhoneNumber($this->input('phone_country'))],
            ...$this->phonePartRules(),
            ...$this->exclusiveLocationSelectionRules(['required']),
            'identification_type' => [
                'bail',
                'required',
                Rule::in($identityTypes),
            ],
            'identification_document' => [
                'bail',
                Rule::requiredIf($mustUploadIdentity),
                'nullable',
                File::types(self::DOCUMENT_TYPES)->max(self::DOCUMENT_MAX_KB),
            ],
            'identity_issuing_country_code' => [
                'required', 'string', 'size:2', Rule::in($countryCodes),
            ],
            'resume' => [
                'bail',
                Rule::requiredIf($profileRequirements['resume']),
                'nullable',
                File::types(self::RESUME_TYPES)->max(self::DOCUMENT_MAX_KB),
            ],
            ...$this->screeningAnswerRules($job instanceof Job ? $job->screeningQuestions : collect()),
        ];
    }

    /** @return array<string, mixed> */
    private function screeningAnswerRules(iterable $questions): array
    {
        $questions = collect($questions);
        if ($questions->isEmpty()) {
            return ['screening_answers' => ['prohibited']];
        }

        $keys = $questions->pluck('question_key')->all();
        $rules = ['screening_answers' => ['required', 'array:'.implode(',', $keys)]];

        /** @var JobScreeningQuestion $question */
        foreach ($questions as $question) {
            $field = 'screening_answers.'.$question->question_key;
            $presence = ['required'];

            $rules[$field] = match ($question->type) {
                JobScreeningQuestionType::ShortText => [...$presence, 'string', 'max:250'],
                JobScreeningQuestionType::LongText => [...$presence, 'string', 'max:5000'],
                JobScreeningQuestionType::SingleSelect => [...$presence, 'string', Rule::in($question->options ?? [])],
                JobScreeningQuestionType::MultiSelect => [...$presence, 'array', 'min:1', 'max:'.count($question->options ?? [])],
                JobScreeningQuestionType::YesNo => [...$presence, Rule::in(['yes', 'no'])],
            };

            if ($question->type === JobScreeningQuestionType::MultiSelect) {
                $rules[$field.'.*'] = ['required', 'string', Rule::in($question->options ?? []), 'distinct:ignore_case'];
            }
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens, and apostrophes.',
            'middle_name.regex' => 'The middle name may only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens, and apostrophes.',
            'identification_type.in' => 'Select a supported identification method.',
            'identity_issuing_country_code.required' => 'Select the country that issued this identification document.',
            'profile_image.dimensions' => sprintf(
                'The profile photo must be between %dx%d and %dx%d pixels.',
                self::PROFILE_IMAGE_MIN_WIDTH,
                self::PROFILE_IMAGE_MIN_HEIGHT,
                self::PROFILE_IMAGE_MAX_WIDTH,
                self::PROFILE_IMAGE_MAX_HEIGHT,
            ),
            'resume.required' => 'Upload your résumé / CV.',
            'screening_answers.*.required' => 'Answer this required screening question.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'profile_image' => 'profile photo',
            'first_name' => 'first name',
            'middle_name' => 'middle name',
            'last_name' => 'last name',
            'country_code' => 'country',
            'state' => 'state or region',
            'city' => 'city',
            'local_government_area' => 'local government area',
            'identification_type' => 'identification method',
            'identification_document' => 'identification document',
            'identity_issuing_country_code' => 'issuing country',
            'resume' => 'résumé / CV',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (ApplicationUploadLimits::messagesForUploadViolations(
                ApplicationUploadLimits::violationsForFiles($this->allFiles()),
            ) as $message) {
                $validator->errors()->add('uploads', $message);
            }

            $job = $this->route('job');
            if (! $job instanceof Job) {
                return;
            }

            $user = $this->user();
            if ($user && ApplicationForm::query()->where('job_id', $job->id)->where('user_id', $user->id)->exists()) {
                $validator->errors()->add('job', 'You have already applied for this job.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $countryCode = $this->input('country_code');
        $countryCode = is_string($countryCode) && filled($countryCode) ? mb_strtoupper(trim($countryCode)) : null;
        $country = collect(app(LocationService::class)->countries())->firstWhere('value', $countryCode);
        $issuerCode = $this->input('identity_issuing_country_code');
        $issuerCode = is_string($issuerCode) && filled($issuerCode) ? mb_strtoupper(trim($issuerCode)) : null;
        $this->merge([
            'country_code' => $countryCode,
            'country' => $country['label'] ?? null,
            'identity_issuing_country_code' => $issuerCode,
        ]);
        $this->normalizeLocationForCountry();
        $this->normalizePhoneInput();

        $job = $this->route('job');
        if ($job instanceof Job && ! $this->hasFile('identification_document') && $this->user()) {
            $savedIdentity = app(ApplicantProfileService::class)->availableIdentificationDocument($this->user());
            if ($savedIdentity) {
                $this->merge([
                    'identification_type' => $savedIdentity->document_type->value,
                    'identity_issuing_country_code' => $this->input('identity_issuing_country_code', $savedIdentity->issuing_country_code),
                ]);
            }
        }
    }

    /** @return array<string, mixed> */
    private function applicationProfileRequirements(): array
    {
        $user = $this->user();

        if (! $user) {
            return ['resume' => true, 'profile_image' => true, 'identification' => true];
        }

        $profile = app(ApplicantProfileService::class)->forUser($user);

        return app(ApplicantProfileChecklistService::class)->applicationRequirements(
            $user,
            $profile,
        );
    }
}
