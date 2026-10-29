<?php

namespace App\Http\Requests;

use App\Models\Job;
use App\Models\Organization;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateOrganizationSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $organization = $this->route('organization');
        if (! $organization instanceof Organization || ! $this->user()?->isOrganization()) {
            return false;
        }
        abort_unless(app(OrganizationContext::class)->organizationId() === $organization->id, 409, 'Your active organization changed. Reload Settings before saving.');

        return $this->user()->can('update', $organization);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255', 'not_regex:/[\x00-\x1f\x7f]/'],
            'about' => ['nullable', 'string', 'max:2000'],
            'careers_url' => ['nullable', 'url', 'max:255'],
            'public_contact_email' => ['nullable', 'email', 'max:255'],
            'public_contact_phone' => ['nullable', 'string', 'max:50', 'not_regex:/[\x00-\x1f\x7f]/'],
            'company_logo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
            'remove_company_logo' => ['nullable', 'boolean'],
            'default_interview_timezone' => ['nullable', 'timezone:all', 'max:100'],
            'default_interview_contact_name' => ['nullable', 'string', 'max:120', 'not_regex:/[\x00-\x1f\x7f]/'],
            'default_interview_contact_phone' => ['nullable', 'string', 'max:50', 'not_regex:/[\x00-\x1f\x7f]/'],
            'default_employment_type' => ['nullable', Rule::in(array_keys(Job::EMPLOYMENT_TYPES))],
            'default_application_instructions' => ['nullable', 'string', 'max:2000'],
            'organization_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'role' => ['prohibited'],
        ];
    }
}
