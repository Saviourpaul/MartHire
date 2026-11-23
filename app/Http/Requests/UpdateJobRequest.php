<?php

namespace App\Http\Requests;

use App\Enums\JobStatus;
use App\Http\Requests\Concerns\ValidatesJobScreeningQuestions;
use App\Models\Job;
use App\Services\JobCountryService;
use App\Support\JobDescription;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class UpdateJobRequest extends FormRequest
{
    use ValidatesJobScreeningQuestions;

    public function authorize(): bool
    {
        $job = $this->route('job');

        return $job instanceof Job && ($this->user()?->can('update', $job) ?? false);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('country_code'))) {
            $this->merge(['country_code' => strtoupper(trim($this->input('country_code')))]);
        }

        $this->merge(['requires_identity_document' => $this->boolean('requires_identity_document')]);
        if (! $this->boolean('requires_identity_document')) {
            $this->merge(['identity_requirement_reason' => null]);
        }
        $this->normalizeScreeningQuestionDefinitions();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => [
                'bail', 'required', 'string', 'max:'.JobDescription::MAX_LENGTH,
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (! app(JobDescription::class)->hasText($value)) {
                        $fail('The description must contain text about the role.');
                    }
                },
            ],
            'company' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'country_code' => ['bail', 'required', 'string', 'size:2', Rule::in(array_keys(app(JobCountryService::class)->all()))],
            'employment_type' => ['required', 'string', Rule::in(array_keys(Job::employmentTypeOptions()))],
            'workplace_type' => ['required', 'string', Rule::in(array_keys(Job::workplaceTypeOptions()))],
            'logo' => $this->hasFile('logo')
                ? ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp', 'gif'])->max(2048)]
                : ['nullable', 'string', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'requires_identity_document' => ['required', 'boolean'],
            'identity_requirement_reason' => [
                Rule::requiredIf(fn (): bool => $this->boolean('requires_identity_document')),
                Rule::prohibitedIf(fn (): bool => ! $this->boolean('requires_identity_document')),
                'nullable', 'string', 'min:10', 'max:255',
            ],
            'start_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:start_date'],
            'action' => ['required', Rule::in(['publish', 'save_draft'])],
            ...$this->screeningQuestionRules(allowExisting: true),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateScreeningQuestionOwnership($validator, allowExisting: true));
    }

    public function resolvedStatus(): JobStatus
    {
        return $this->input('action') === 'save_draft' ? JobStatus::Draft : JobStatus::Open;
    }
}
