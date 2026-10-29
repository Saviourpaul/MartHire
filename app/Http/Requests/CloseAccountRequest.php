<?php

namespace App\Http\Requests;

class CloseAccountRequest extends SensitiveAccountActionRequest
{
    protected $errorBag = 'userDeletion';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [...parent::rules(), 'password' => ['nullable', 'string', 'max:1024']];
    }
}
