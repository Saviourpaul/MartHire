<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class UpdateAccountPasswordRequest extends SensitiveAccountActionRequest
{
    protected $errorBag = 'updatePassword';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [...parent::rules(), 'password' => ['required', 'string', 'min:8', 'max:1024', Password::defaults(), 'confirmed']];
    }
}
