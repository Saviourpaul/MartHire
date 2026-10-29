<?php

namespace App\Http\Requests;

use App\Enums\SocialPlatform;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && $this->user()->isActive() && $this->user()->hasVerifiedEmail();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'version' => ['required', 'integer', 'min:1'],
            'display_name' => ['required', 'string', 'max:120', 'not_regex:/[\x00-\x1f\x7f]/'],
            'mail_from_name' => ['nullable', 'string', 'max:120', 'not_regex:/[\x00-\x1f\x7f]/'],
            'mail_from_address' => ['nullable', 'email', 'max:254'],
            'support_email' => ['nullable', 'email', 'max:254'],
            'support_phone' => ['nullable', 'string', 'max:50', 'not_regex:/[\x00-\x1f\x7f]/'],
            'support_address' => ['nullable', 'string', 'max:1000', 'not_regex:/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/'],
            'support_url' => ['nullable', 'url:https', 'max:2048'],
            'privacy_url' => ['nullable', 'url:https', 'max:2048'],
            'terms_url' => ['nullable', 'url:https', 'max:2048'],
            'public_registration_enabled' => ['required', 'boolean'],
            'site_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'extensions:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
            'icon' => ['nullable', 'image', 'mimes:png', 'extensions:png', 'max:2048'],
            'remove_site_logo' => ['sometimes', 'boolean'],
            'remove_icon' => ['sometimes', 'boolean'],
            'site_logo_path' => ['prohibited'],
            'logo_light_path' => ['prohibited'],
            'logo_dark_path' => ['prohibited'],
            'icon_path' => ['prohibited'],
            'updated_by' => ['prohibited'],
        ];
        foreach (SocialPlatform::cases() as $platform) {
            $rules[$platform->value] = ['nullable', 'url:https', 'max:2048'];
        }

        return $rules;
    }
}
