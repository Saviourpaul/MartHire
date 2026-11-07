<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSoundPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() && $this->user()->hasVerifiedEmail();
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'user_id' => ['prohibited'],
            'role' => ['prohibited'],
        ];
    }
}
