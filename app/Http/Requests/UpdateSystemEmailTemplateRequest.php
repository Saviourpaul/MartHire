<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isAdmin() && $user->isActive() && $user->hasVerifiedEmail();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255', 'not_regex:/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/'],
            'body' => ['required', 'string', 'max:20000'],
        ];
    }
}
