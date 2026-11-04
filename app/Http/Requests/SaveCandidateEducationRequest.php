<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveCandidateEducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isApplicant() && $this->user()->isActive() && $this->user()->hasVerifiedEmail();
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'institution' => ['required', 'string', 'max:255'],
            'qualification' => ['required', 'string', 'max:255'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:5000'],
            'user_id' => ['prohibited'],
            'applicant_profile_id' => ['prohibited'],
        ];
    }
}
