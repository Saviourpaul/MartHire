<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCandidateSkillsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isApplicant() && $this->user()->isActive() && $this->user()->hasVerifiedEmail();
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'skills' => ['present', 'array', 'max:30'],
            'skills.*' => ['required', 'string', 'min:1', 'max:80', 'distinct:ignore_case'],
            'user_id' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $skills = $this->input('skills');
        if (is_string($skills)) {
            $skills = collect(explode(',', $skills))->map(fn (string $skill) => trim($skill))->filter()->unique(fn (string $skill) => mb_strtolower($skill))->values()->all();
            $this->merge([
                'skills' => $skills,
            ]);
        }
    }
}
