<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SensitiveAccountActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isActive() === true && $this->user()->hasVerifiedEmail();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['nullable', 'string', 'max:1024'],
            'challenge_id' => ['nullable', 'uuid'],
            'code' => ['nullable', 'string', 'regex:/\A[0-9]{6}\z/'],
        ];
    }
}
