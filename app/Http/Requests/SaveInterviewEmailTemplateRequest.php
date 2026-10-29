<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class SaveInterviewEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->attributes->get('organization');

        return $organization instanceof Organization
            && ($this->user()?->can('manage_email_templates', $organization) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255', 'not_regex:/[\r\n]/'],
            'body' => ['required', 'string', 'max:20000'],
            'is_active' => ['sometimes', 'boolean'],
            'organization_id' => ['prohibited'],
        ];
    }
}
