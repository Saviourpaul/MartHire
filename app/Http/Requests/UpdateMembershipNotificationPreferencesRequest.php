<?php

namespace App\Http\Requests;

use App\Enums\NotificationCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMembershipNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOrganization() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $keys = array_column(NotificationCategory::forUser($this->user()), 'value');

        return [
            'membership_email_preferences' => ['required', 'array:'.implode(',', $keys)],
            'membership_email_preferences.*' => ['required', Rule::in(['inherit', 'on', 'off'])],
        ];
    }
}
