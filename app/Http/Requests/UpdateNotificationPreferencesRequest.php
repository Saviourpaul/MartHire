<?php

namespace App\Http\Requests;

use App\Enums\NotificationCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && NotificationCategory::forUser($this->user()) !== [];
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $keys = array_column(NotificationCategory::forUser($this->user()), 'value');

        return [
            'email_preferences' => ['required', 'array:'.implode(',', $keys)],
            'email_preferences.*' => ['required', 'boolean'],
        ];
    }
}
