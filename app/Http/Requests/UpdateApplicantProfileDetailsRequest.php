<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicantProfileDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isApplicant() && $this->user()->isActive() && $this->user()->hasVerifiedEmail();
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'professional_headline' => ['nullable', 'string', 'max:160'],
            'about' => ['nullable', 'string', 'max:3000'],
            'links' => ['required', 'array:linkedin,github,website'],
            'links.linkedin' => ['nullable', 'url', 'max:2048'],
            'links.github' => ['nullable', 'url', 'max:2048'],
            'links.website' => ['nullable', 'url', 'max:2048'],
            'user_id' => ['prohibited'],
            'role' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $links = $this->input('links', []);
        if (is_array($links)) {
            $this->merge(['links' => array_map(fn ($url) => is_string($url) ? trim($url) : $url, $links)]);
        }

    }
}
