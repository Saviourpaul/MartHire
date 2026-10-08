<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesLocationSelection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class ProfileUpdateRequest extends FormRequest
{
    use ValidatesLocationSelection;

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
            'phone' => $profileFieldRule,
            'address' => $profileFieldRule,
            'country_code' => [$this->user()->isApplicant() ? 'required' : 'nullable', 'string', 'regex:/^[A-Z]{2}$/D'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.in' => 'Your email address cannot be changed from your profile.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateLocation($validator, $this->user()->isApplicant());
        });
    }
}
