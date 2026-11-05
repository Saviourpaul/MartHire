<?php

namespace App\Http\Requests;

use App\Models\Job;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SuspendJobRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $job = $this->route('job');

        return $job instanceof Job
            && ($this->user()?->can('suspend', $job) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'suspension_reason' => ['required', 'string', 'max:2000'],
        ] + array_fill_keys([
            'organization_id', 'employer_id', 'slug', 'country_code',
            'status', 'title', 'description', 'company', 'category', 'location',
            'employment_type', 'logo', 'start_date', 'due_date',
            'suspended_at', 'suspended_by',
        ], ['prohibited']);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('suspension_reason'))) {
            $this->merge(['suspension_reason' => trim($this->input('suspension_reason'))]);
        }
    }
}
