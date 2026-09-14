<?php

namespace App\Http\Requests;

use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\NigeriaState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreApplicationFormRequest extends FormRequest
{
    public const PROFILE_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'webp'];

    public const PROFILE_IMAGE_MAX_KB = 5120;

    public const PROFILE_IMAGE_MIN_WIDTH = 200;

    public const PROFILE_IMAGE_MIN_HEIGHT = 200;

    public const PROFILE_IMAGE_MAX_WIDTH = 2000;

    public const PROFILE_IMAGE_MAX_HEIGHT = 2000;

    public const DOCUMENT_TYPES = ['pdf', 'jpg', 'jpeg', 'png'];

    public const DOCUMENT_MAX_KB = 5120;

    private const PERSON_NAME_PATTERN = '/^[\pL\s\'-]+$/u';

    private const PHONE_PATTERN = '/^\+?[0-9\s().-]{7,20}$/';

    private const ZIPCODE_PATTERN = '/^[A-Za-z0-9\s-]{3,20}$/';

    public function authorize(): bool
    {
        $job = $this->route('job');
        $user = $this->user();

        return $job instanceof Job
            && $user?->isApplicant()
            && $job->isAcceptingApplications();
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
            'phone' => ['bail', 'required', 'string', 'regex:'.self::PHONE_PATTERN],
            'nationality' => ['bail', 'required', 'string', 'min:2', 'max:100', 'regex:'.self::PERSON_NAME_PATTERN],
            'date_of_birth' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before:today'],
            'gender' => ['bail', 'required', Rule::in(['male', 'female', 'other'])],
            'marital_status' => ['bail', 'required', Rule::in(['single', 'married', 'Other'])],
            'state_of_origin' => ['bail', 'required', 'string', 'max:255', Rule::exists('nigeria_states', 'name')],
            'local_government_area' => ['bail', 'required', 'string', 'max:255'],
            'address' => ['bail', 'required', 'string', 'min:5', 'max:255'],
            'zipcode' => ['bail', 'required', 'string', 'regex:'.self::ZIPCODE_PATTERN],
            'nin_number' => ['bail', 'required', 'digits:11'],
            'nin_document' => [
                'bail',
                'required',
                File::types(self::DOCUMENT_TYPES)->max(self::DOCUMENT_MAX_KB),
            ],
            'bvn_number' => ['bail', 'required', 'digits:11'],
            'bvn_document' => [
                'bail',
                'required',
                File::types(self::DOCUMENT_TYPES)->max(self::DOCUMENT_MAX_KB),
            ],
            'education_documents' => ['bail', 'required', 'array', 'min:1', 'max:10'],
            'education_documents.*.type' => [
                'bail',
                'required',
                Rule::in(['ssce', 'ond', 'bsc', 'bed', 'nysc', 'msc', 'phd', 'other']),
            ],
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
            'phone.regex' => 'Enter a valid phone number using 7 to 20 digits, with an optional leading plus sign.',
            'nationality.regex' => 'The nationality may only contain letters, spaces, hyphens, and apostrophes.',
            'date_of_birth.date_format' => 'Enter the date of birth in YYYY-MM-DD format.',
            'date_of_birth.after_or_equal' => 'Enter a realistic date of birth.',
            'date_of_birth.before' => 'The date of birth must be before today.',
            'zipcode.regex' => 'The zipcode may only contain letters, numbers, spaces, and hyphens.',
            'nin_number.digits' => 'The NIN number must be exactly 11 numeric digits.',
            'bvn_number.digits' => 'The BVN number must be exactly 11 numeric digits.',
            'profile_image.dimensions' => sprintf(
                'The profile photo must be between %dx%d and %dx%d pixels.',
                self::PROFILE_IMAGE_MIN_WIDTH,
                self::PROFILE_IMAGE_MIN_HEIGHT,
                self::PROFILE_IMAGE_MAX_WIDTH,
                self::PROFILE_IMAGE_MAX_HEIGHT
            ),
            'education_documents.max' => 'You can upload a maximum of 10 education documents.',
            'education_documents.*.type.required' => 'Select the document type for each education document.',
            'education_documents.*.file.required' => 'Upload a file for each education document.',
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
            'state_of_origin' => 'state of origin',
            'local_government_area' => 'local government area',
            'nin_number' => 'NIN number',
            'nin_document' => 'NIN document',
            'bvn_number' => 'BVN number',
            'bvn_document' => 'BVN document',
            'education_documents' => 'education documents',
            'education_documents.*.type' => 'education document type',
            'education_documents.*.file' => 'education document file',
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

            $state = NigeriaState::query()
                ->where('name', (string) $this->input('state_of_origin'))
                ->first();

            if (! $state) {
                return;
            }

            $hasLga = $state->localGovernmentAreas()
                ->where('name', (string) $this->input('local_government_area'))
                ->exists();

            if (! $hasLga) {
                $validator->errors()->add('local_government_area', 'Select a local government area in the selected state.');
            }
        });
    }
}
