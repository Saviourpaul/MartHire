<?php

namespace App\Http\Requests;

use App\Models\Job;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCandidateEmploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isApplicant() && $this->user()->isActive() && $this->user()->hasVerifiedEmail();
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'company' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', Rule::in(array_keys(Job::EMPLOYMENT_TYPES))],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'before_or_equal:today'],
            'is_current' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
            'user_id' => ['prohibited'],
            'applicant_profile_id' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('is_current')) {
            $this->merge(['end_date' => null]);
        }
    }
}
