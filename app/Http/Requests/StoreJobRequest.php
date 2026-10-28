<?php

namespace App\Http\Requests;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Support\JobDescription;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Job::class) ?? false;
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
            'location' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'string', Rule::in(array_keys(Job::employmentTypeOptions()))],
            'logo' => $this->hasFile('logo')
                ? ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048']
                : ['nullable', 'string', 'max:2048'],
            'start_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:start_date', 'after_or_equal:today'],
            'action' => ['required', Rule::in(['publish', 'save_draft'])],
        ];
    }

    public function resolvedStatus(): JobStatus
    {
        return $this->input('action') === 'save_draft' ? JobStatus::Draft : JobStatus::Open;
    }
}
