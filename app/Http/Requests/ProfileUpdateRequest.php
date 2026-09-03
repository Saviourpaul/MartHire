<?php

namespace App\Http\Requests;

use App\Models\NigeriaState;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class ProfileUpdateRequest extends FormRequest
{
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

            'profile_image' => [
                ...$imageRule,
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp', 'gif'])
                    ->max(2048),
            ],
            'date_of_birth' => $dateOfBirthRule,
            'phone' => $profileFieldRule,
            'address' => $profileFieldRule,
            'nationality' => $profileFieldRule,
            'state_of_origin' => [
                ...$profileFieldRule,
                Rule::exists('nigeria_states', 'name'),
            ],
            'local_government_area' => $profileFieldRule,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('state_of_origin') || ! $this->filled('local_government_area')) {
                return;
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
