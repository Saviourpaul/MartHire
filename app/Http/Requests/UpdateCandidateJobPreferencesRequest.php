<?php

namespace App\Http\Requests;

use App\Enums\JobAlertFrequency;
use App\Models\Job;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidateJobPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isApplicant() && $user->isActive() && $user->hasVerifiedEmail();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'version' => ['nullable', 'integer', 'min:1'],
            'job_alert_frequency' => ['required', Rule::in(JobAlertFrequency::values())],
            'profile_visible_to_employers' => ['required', 'boolean'],
            'preferred_roles' => ['nullable', 'array', 'max:20'],
            'preferred_roles.*' => ['string', 'max:100'],
            'preferred_locations' => ['nullable', 'array', 'max:20'],
            'preferred_locations.*' => ['string', 'max:100'],
            'preferred_job_type' => ['nullable', Rule::in(array_keys(Job::EMPLOYMENT_TYPES))],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:100000000', 'gte:salary_min'],
        ];
    }

    /**
     * Normalise comma-separated role/location text fields into trimmed arrays.
     */
    protected function prepareForValidation(): void
    {
        foreach (['preferred_roles', 'preferred_locations'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([
                    $field => collect(explode(',', $value))
                        ->map(fn (string $item): string => trim($item))
                        ->filter()
                        ->values()
                        ->all(),
                ]);
            }
        }
    }
}
