<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CompleteOrganizationDemoSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_first_name' => ['required', 'string', 'max:120', 'not_regex:/[\x00-\x1f\x7f]/'],
            'contact_last_name' => ['required', 'string', 'max:120', 'not_regex:/[\x00-\x1f\x7f]/'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
        ];
    }
}
