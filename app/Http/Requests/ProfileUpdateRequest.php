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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mirrorNigerianLocalGovernmentArea();
        $this->normalizePhoneInput();
    }
}
