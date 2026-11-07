<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() && $this->user()->hasVerifiedEmail();
    }

    public function rules(): array
    {
        return [
            'email_preferences' => ['sometimes', 'array'],
            'email_preferences.*' => ['boolean'],
            'membership_email_preferences' => [
                Rule::prohibitedIf(! $this->user()?->isOrganization()),
                'sometimes', 'array',
            ],
            'membership_email_preferences.*' => ['nullable', Rule::in(['on', 'off'])],
        ];
    }
}
