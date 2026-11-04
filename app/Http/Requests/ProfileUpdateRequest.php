<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesInternationalPhone;
use App\Http\Requests\Concerns\ValidatesLocationSelection;
use App\Rules\InternationalPhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ProfileUpdateRequest extends FormRequest
{
    use NormalizesInternationalPhone, ValidatesLocationSelection;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $profileFieldRule = $this->user()->isApplicant() ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:255'];
        $dateOfBirthRule = $this->user()->isApplicant() ? ['required', 'date', 'before:today'] : ['nullable', 'date', 'before:today'];
        $imageRule = $this->user()->isApplicant() && ! $this->user()->profile_image_path ? ['required'] : ['nullable'];
        $presence = $this->user()->isApplicant() ? ['required'] : ['nullable'];
        $applicantRule = $this->user()->isApplicant();

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::in([$this->user()->email])],

            'profile_image' => [
                ...$imageRule,
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp', 'gif'])
                    ->max(2048),
            ],
            'date_of_birth' => $dateOfBirthRule,
            'phone' => [$this->user()->isApplicant() ? 'required' : 'nullable', 'bail', 'string', 'max:80', new InternationalPhoneNumber($this->input('phone_country'))],
            ...$this->phonePartRules(),
            'address' => $profileFieldRule,

            'gender_identity' => $applicantRule ? ['nullable', 'in:male,female'] : ['prohibited'],
            'disability_status' => $applicantRule ? ['nullable', 'in:yes,no'] : ['prohibited'],
            'disability_details' => $applicantRule
                ? ['exclude_unless:disability_status,yes', 'required', 'string', 'max:1000']
                : ['prohibited'],
            'professional_headline' => $applicantRule ? ['nullable', 'string', 'max:160'] : ['prohibited'],
            'about' => $applicantRule ? ['nullable', 'string', 'max:3000'] : ['prohibited'],

            ...$this->locationSelectionRules($presence),
        ];
    }

    public function messages(): array
    {
        return [
            'email.in' => 'Your email address cannot be changed from your profile.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'country_code' => 'country',
            'state' => 'state',
            'city' => 'city',
            'gender_identity' => 'gender identity',
            'disability_status' => 'disability status',
            'disability_details' => 'disability details',
            'professional_headline' => 'professional headline',
            'about' => 'about you',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mirrorNigerianLocalGovernmentArea();
        $this->normalizePhoneInput();
    }
}
