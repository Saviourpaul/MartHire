<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AcceptTeamInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => [$this->user() ? 'nullable' : 'required', 'string', 'max:255'],
            'last_name' => [$this->user() ? 'nullable' : 'required', 'string', 'max:255'],
            'password' => [$this->user() ? 'nullable' : 'required', 'confirmed', Password::min(8)->mixedCase()->letters()->numbers()->symbols()],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
        ];
    }
}
