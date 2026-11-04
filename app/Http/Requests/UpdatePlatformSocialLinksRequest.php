<?php

namespace App\Http\Requests;

use App\Enums\SocialPlatform;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSocialLinksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && $this->user()->isActive() && $this->user()->hasVerifiedEmail();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = ['version' => ['required', 'integer', 'min:1']];

        foreach (SocialPlatform::cases() as $platform) {
            $rules[$platform->value] = ['nullable', 'url:https', 'max:2048'];
        }

        return $rules;
    }
}
