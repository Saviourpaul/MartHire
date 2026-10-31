<?php

namespace App\Http\Requests;

use App\Enums\ApplicationDocumentType;
use App\Http\Requests\Concerns\NormalizesInternationalPhone;
use App\Http\Requests\Concerns\ValidatesLocationSelection;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Rules\InternationalPhoneNumber;
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
            'gender' => ['bail', 'required', Rule::in(['male', 'female', 'other'])],
            'marital_status' => ['bail', 'required', Rule::in(['single', 'married', 'Other'])],
            ...$this->exclusiveLocationSelectionRules(['bail', 'required']),
            'address' => ['bail', 'required', 'string', 'min:5', 'max:255'],
            'identification_type' => [
                'bail',
                'required',
                Rule::in(array_map(
                    fn (ApplicationDocumentType $type): string => $type->value,
                    ApplicationDocumentType::identityTypes()
                )),
            ],
            'identification_document' => [
                'bail',
                'required',
                File::types(self::DOCUMENT_TYPES)->max(self::DOCUMENT_MAX_KB),
            ],
            'resume' => [
                'bail',
                'required',
                File::types(self::RESUME_TYPES)->max(self::DOCUMENT_MAX_KB),
            ],
            'education_documents' => ['bail', 'required', 'array', 'min:1', 'max:10'],
            'education_documents.*.name' => ['bail', 'required', 'string', 'min:2', 'max:100'],
            'education_documents.*.file' => [
                'bail',
                'required',
                File::types(self::DOCUMENT_TYPES)->max(self::DOCUMENT_MAX_KB),
            ],
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
    }
}
